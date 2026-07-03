<?php

namespace App\Console\Commands;

use App\Jobs\FetchWallpapersJob;
use App\Models\FetchTask;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchManagerCommand extends Command
{
    protected $signature = 'fetch:manager
                            {--api=danbooru : Source API (danbooru/gelbooru/zerochan)}
                            {--tags= : (Opsional) Fetch tag spesifik manual}
                            {--max-concurrent=3 : Maksimal task running bersamaan}
                            {--force-populate : Force populate dari Core tanpa kondisi}
                            {--skip-populate : Skip auto-populate}
                            {--timeout=10 : Timeout stuck task dalam menit}
                            {--debug : Enable debug logging}';

    protected $description = 'Fetch manager sinkron, mengisi slot kosong sesuai prioritas air terjun.';

    private int $windowMinutes = 2;
    private int $allowedSeriesId = 1;

    public function handle(): int
    {
        $sourceApi = (string) $this->option('api');
        $specificTags = $this->option('tags');
        $maxConcurrent = (int) $this->option('max-concurrent');
        $forcePopulate = (bool) $this->option('force-populate');
        $skipPopulate = (bool) $this->option('skip-populate');
        $timeout = (int) $this->option('timeout');
        $debug = (bool) $this->option('debug');

        $this->windowMinutes = max(2, (int) floor($timeout / 5));
        $appName = config('app.name', 'NTE App');

        if ($maxConcurrent < 1 || $maxConcurrent > 10) {
            $this->error('❌ Parameter --max-concurrent harus antara 1-10');
            return self::FAILURE;
        }

        if ($timeout < 5 || $timeout > 60) {
            $this->error('❌ Parameter --timeout harus antara 5-60 menit');
            return self::FAILURE;
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('⏰ ' . now()->format('Y-m-d H:i:s'));
        $this->info("📦 Fetch Manager: {$appName} ({$sourceApi}) | Series ID: {$this->allowedSeriesId}");
        $this->info("🔢 Max Concurrent: {$maxConcurrent} | Timeout: {$timeout} min | Window: {$this->windowMinutes} min");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 1. Reset Stuck Tasks
        $resetCount = $this->resetStuckTasks($sourceApi, $timeout);
        if ($resetCount > 0) {
            $this->info("🔄 Reset {$resetCount} stuck task(s)");
        }

        // 2. Handle Manual Tags
        if ($specificTags) {
            $this->handleManualTagsInput($specificTags, $sourceApi);
        }

        // 3. Hitung Ketersediaan Slot
        $recentlyDispatchedCount = $this->countRecentlyDispatched($sourceApi);
        $availableSlots = max(0, $maxConcurrent - $recentlyDispatchedCount);

        $this->info("📊 Recently dispatched: {$recentlyDispatchedCount}/{$maxConcurrent} | Slot tersedia: {$availableSlots}");

        if ($availableSlots <= 0) {
            $this->comment('✓ Semua slot sedang terpakai. Menunggu job selesai.');
            $this->showRecentActiveTasks($sourceApi);
            return self::SUCCESS;
        }

        $tasksToDispatch = collect();

        // ====================================================================
        // SISTEM AIR TERJUN (WATERFALL) UNTUK MENGISI SLOT KOSONG
        // ====================================================================

        // PRIORITAS 1: Lanjutkan running/rerunning yang butuh didispatch
        if ($availableSlots > 0) {
            $p1Tasks = $this->claimContinuationTasks($sourceApi, $availableSlots);
            if ($p1Tasks->isNotEmpty()) {
                $this->line("🎯 Prioritas 1: Memilih {$p1Tasks->count()} task aktif (running/rerunning)");
                $tasksToDispatch = $tasksToDispatch->merge($p1Tasks);
                $availableSlots -= $p1Tasks->count();
            }
        }

        // PRIORITAS 2: Ambil task Pending
        if ($availableSlots > 0) {
            $p2Tasks = $this->claimPendingTasks($sourceApi, $availableSlots);
            if ($p2Tasks->isNotEmpty()) {
                $this->line("🆕 Prioritas 2: Memilih {$p2Tasks->count()} task pending");
                $tasksToDispatch = $tasksToDispatch->merge($p2Tasks);
                $availableSlots -= $p2Tasks->count();
            }
        }

        // Cek apakah antrian benar-benar kosong untuk Prioritas 3 & 4
        $hasActiveOrPendingQueue = FetchTask::where('source_api', $sourceApi)
            ->whereIn('status', ['pending', 'running', 'rerunning'])
            ->exists();

        // PRIORITAS 3: Auto Populate (Hanya jalan jika queue aktif/pending kosong)
        if ($availableSlots > 0 && !$hasActiveOrPendingQueue && !$skipPopulate) {
            $this->line('🔍 Prioritas 3: Antrian kosong, mencoba auto-populate dari Core...');
            $newCount = $this->smartAutoPopulateOnlyWhenQueueEmpty($sourceApi, $forcePopulate);
            
            if ($newCount > 0) {
                $this->info("✓ Auto-populate berhasil menambahkan {$newCount} task baru.");
                
                // Ambil task pending yang baru saja dipopulate
                $p3Tasks = $this->claimPendingTasks($sourceApi, $availableSlots);
                if ($p3Tasks->isNotEmpty()) {
                    $this->line("🆕 Memilih {$p3Tasks->count()} task dari hasil populate");
                    $tasksToDispatch = $tasksToDispatch->merge($p3Tasks);
                    $availableSlots -= $p3Tasks->count();
                    $hasActiveOrPendingQueue = true; // Queue tidak lagi kosong
                }
            }
        }

        // PRIORITAS 4: Refresh Completed > 24 jam (Hanya jika benar-benar tidak ada yang lain)
        if ($availableSlots > 0 && !$hasActiveOrPendingQueue) {
            $p4Tasks = $this->claimCompletedTasksForRerun($sourceApi, $availableSlots);
            if ($p4Tasks->isNotEmpty()) {
                $this->line("🔄 Prioritas 4: Memilih {$p4Tasks->count()} task completed lama untuk di-rerun");
                $tasksToDispatch = $tasksToDispatch->merge($p4Tasks);
                $availableSlots -= $p4Tasks->count();
            }
        }

        // ====================================================================
        // DISPATCH
        // ====================================================================

        if ($tasksToDispatch->isEmpty()) {
            $this->comment('✓ Tidak ada job eligible yang bisa dijalankan saat ini.');
            $this->showStats($sourceApi);
            return self::SUCCESS;
        }

        return $this->dispatchTasks($tasksToDispatch, $sourceApi, $appName, $maxConcurrent, $recentlyDispatchedCount);
    }

    private function dispatchTasks($tasks, string $sourceApi, string $appName, int $maxConcurrent, int $recentlyDispatchedCount): int
    {
        $dispatched = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            try {
                FetchWallpapersJob::dispatch($task->id)->afterCommit();

                $mode = $task->status === 'rerunning' ? 'RERUN' : 'FULL';
                $icon = $task->status === 'rerunning' ? '🔄' : '🆕';

                $this->info("  {$icon} Dispatched [{$mode}]: {$task->tag_name} (ID: {$task->id}, Page: {$task->current_page})");
                $dispatched++;

                Log::info('[FetchManager] Job dispatched', [
                    'task_id' => $task->id,
                    'tag' => $task->tag_name,
                    'api' => $sourceApi,
                    'status' => $task->status,
                    'page' => $task->current_page,
                    'app' => $appName,
                ]);
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  ✗ Error dispatching [{$task->tag_name}]: {$e->getMessage()}");
                $this->rollbackClaim($task->id);

                Log::error('[FetchManager] Dispatch failed', [
                    'task_id' => $task->id,
                    'tag' => $task->tag_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✓ Summary: Dispatched {$dispatched} job(s)" . ($failed ? ", Failed {$failed}" : ''));
        $this->info('📊 Total active window: ' . ($recentlyDispatchedCount + $dispatched) . "/{$maxConcurrent}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function countRecentlyDispatched(string $sourceApi): int
    {
        return FetchTask::where('source_api', $sourceApi)
            ->whereIn('status', ['running', 'rerunning'])
            ->whereNotNull('last_run_at')
            ->where('last_run_at', '>=', now()->subMinutes($this->windowMinutes))
            ->count();
    }

    private function claimContinuationTasks(string $sourceApi, int $limit)
    {
        if ($limit <= 0) return collect();

        $tasks = collect();
        $now = now();
        $cutoff = $now->copy()->subMinutes($this->windowMinutes);

        $candidates = FetchTask::where('source_api', $sourceApi)
            ->whereIn('status', ['running', 'rerunning'])
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_run_at')
                    ->orWhere('last_run_at', '<', $cutoff);
            })
            ->orderByRaw("CASE WHEN status = 'running' THEN 0 ELSE 1 END")
            ->orderBy('current_page', 'desc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $candidate) {
            if ($tasks->count() >= $limit) break;

            DB::transaction(function () use ($candidate, $now, $cutoff, &$tasks) {
                $task = FetchTask::where('id', $candidate->id)
                    ->whereIn('status', ['running', 'rerunning'])
                    ->where(function ($q) use ($cutoff) {
                        $q->whereNull('last_run_at')
                            ->orWhere('last_run_at', '<', $cutoff);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$task) return;

                $task->update([
                    'last_run_at' => $now,
                ]);

                $tasks->push($task->fresh());
            }, 3);
        }

        return $tasks;
    }

    private function claimPendingTasks(string $sourceApi, int $limit)
    {
        if ($limit <= 0) return collect();

        $tasks = collect();
        $now = now();

        $candidates = FetchTask::where('source_api', $sourceApi)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $candidate) {
            if ($tasks->count() >= $limit) break;

            DB::transaction(function () use ($candidate, $now, &$tasks) {
                $task = FetchTask::where('id', $candidate->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$task) return;

                $task->update([
                    'status' => 'running',
                    'current_page' => 1,
                    'last_run_at' => $now,
                ]);

                $tasks->push($task->fresh());
            }, 3);
        }

        return $tasks;
    }

    private function claimCompletedTasksForRerun(string $sourceApi, int $limit)
    {
        if ($limit <= 0) return collect();

        $tasks = collect();
        $now = now();
        $cutoff = $now->copy()->subHours(12);

        $candidates = FetchTask::where('source_api', $sourceApi)
            ->where('status', 'completed')
            ->where('updated_at', '<', $cutoff)
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($candidates as $candidate) {
            if ($tasks->count() >= $limit) break;

            DB::transaction(function () use ($candidate, $now, $cutoff, &$tasks) {
                $task = FetchTask::where('id', $candidate->id)
                    ->where('status', 'completed')
                    ->where('updated_at', '<', $cutoff)
                    ->lockForUpdate()
                    ->first();

                if (!$task) return;

                $task->update([
                    'status' => 'rerunning',
                    'current_page' => 1,
                    'last_run_at' => $now,
                ]);

                $tasks->push($task->fresh());
            }, 3);
        }

        return $tasks;
    }

    private function resetStuckTasks(string $sourceApi, int $timeout): int
    {
        $stuckTasks = FetchTask::where('source_api', $sourceApi)
            ->whereIn('status', ['running', 'rerunning'])
            ->whereNotNull('last_run_at')
            ->where('last_run_at', '<', now()->subMinutes($timeout))
            ->get();

        $count = 0;

        foreach ($stuckTasks as $task) {
            $oldStatus = $task->status;
            $newStatus = $oldStatus === 'rerunning' ? 'completed' : 'pending';

            $task->update([
                'status' => $newStatus,
                'last_run_at' => null,
            ]);

            $count++;

            Log::warning('[FetchManager] Stuck task reset', [
                'task_id' => $task->id,
                'tag' => $task->tag_name,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
            ]);
        }

        return $count;
    }

    private function rollbackClaim(int $taskId): void
    {
        try {
            DB::transaction(function () use ($taskId) {
                $task = FetchTask::where('id', $taskId)->lockForUpdate()->first();

                if (!$task) return;

                if ($task->status === 'running' && (int) $task->current_page === 1) {
                    $task->update([
                        'status' => 'pending',
                        'last_run_at' => null,
                    ]);
                    return;
                }

                if ($task->status === 'rerunning' && (int) $task->current_page === 1) {
                    $task->update([
                        'status' => 'completed',
                        'last_run_at' => null,
                    ]);
                    return;
                }

                $task->update([
                    'last_run_at' => null,
                ]);
            }, 3);
        } catch (\Throwable $e) {
            Log::error("[FetchManager] Rollback claim failed for task {$taskId}: {$e->getMessage()}");
        }
    }

    private function smartAutoPopulateOnlyWhenQueueEmpty(string $sourceApi, bool $forcePopulate = false): int
    {
        $coreCount = $this->getCoreTotalCount($sourceApi);
        $appCount = FetchTask::where('source_api', $sourceApi)->count();

        if (!$forcePopulate && $coreCount <= $appCount) {
            return 0;
        }

        return $this->populateFromCore($sourceApi);
    }

    private function getCoreTotalCount(string $sourceApi): int
    {
        return DB::table('character_api_tags')
            ->join('character_series', 'character_api_tags.character_id', '=', 'character_series.character_id')
            ->where('character_api_tags.source_api', $sourceApi)
            ->where('character_series.series_id', $this->allowedSeriesId)
            ->distinct('character_api_tags.tag_name')
            ->count('character_api_tags.tag_name');
    }

    private function populateFromCore(string $sourceApi): int
    {
        $allCoreTags = collect();

        DB::table('character_api_tags')
            ->select('character_api_tags.tag_name')
            ->join('character_series', 'character_api_tags.character_id', '=', 'character_series.character_id')
            ->where('character_api_tags.source_api', $sourceApi)
            ->where('character_series.series_id', $this->allowedSeriesId)
            ->distinct()
            ->orderBy('character_api_tags.tag_name')
            ->lazy(1000)
            ->each(function ($row) use ($allCoreTags) {
                $tag = trim((string) $row->tag_name);
                if ($tag !== '') {
                    $allCoreTags->push($tag);
                }
            });

        $allCoreTags = $allCoreTags->unique()->values();

        if ($allCoreTags->isEmpty()) {
            return 0;
        }

        $existingTags = FetchTask::where('source_api', $sourceApi)
            ->pluck('tag_name')
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->values()
            ->toArray();

        $newTags = $allCoreTags->diff($existingTags)->values();

        if ($newTags->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($newTags, $sourceApi) {
            $now = now();
            $inserted = 0;

            foreach ($newTags->chunk(500) as $chunk) {
                $rows = $chunk->map(fn ($tag) => [
                    'tag_name' => $tag,
                    'source_api' => $sourceApi,
                    'status' => 'pending',
                    'current_page' => 1,
                    'last_run_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                FetchTask::insert($rows);
                $inserted += count($rows);
            }

            return $inserted;
        }, 3);
    }

    private function handleManualTagsInput(string $specificTags, string $sourceApi): void
    {
        $tagsArray = array_values(array_unique(array_filter(array_map('trim', explode(',', $specificTags)))));

        if (empty($tagsArray)) {
            $this->warn('⚠️ Tidak ada tag yang valid.');
            return;
        }

        $now = now();

        foreach ($tagsArray as $tag) {
            $existing = FetchTask::where('tag_name', $tag)
                ->where('source_api', $sourceApi)
                ->first();

            if ($existing) {
                if (!in_array($existing->status, ['running', 'rerunning'], true)) {
                    $existing->update([
                        'status' => 'pending',
                        'current_page' => 1,
                        'last_run_at' => null,
                        'updated_at' => $now,
                    ]);
                }
            } else {
                FetchTask::create([
                    'tag_name' => $tag,
                    'source_api' => $sourceApi,
                    'status' => 'pending',
                    'current_page' => 1,
                    'last_run_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function showRecentActiveTasks(string $sourceApi): void
    {
        $tasks = FetchTask::where('source_api', $sourceApi)
            ->whereIn('status', ['running', 'rerunning'])
            ->whereNotNull('last_run_at')
            ->where('last_run_at', '>=', now()->subMinutes($this->windowMinutes))
            ->orderBy('last_run_at', 'desc')
            ->get(['id', 'tag_name', 'status', 'current_page', 'last_run_at']);

        foreach ($tasks as $task) {
            $mode = $task->status === 'rerunning' ? 'RERUN' : 'FULL';
            $this->line("  → [{$task->id}] {$task->tag_name} [{$mode}] (page {$task->current_page}, dispatched: {$task->last_run_at->diffForHumans()})");
        }
    }

    private function showStats(string $sourceApi): void
    {
        $stats = [
            'pending' => FetchTask::where('source_api', $sourceApi)->where('status', 'pending')->count(),
            'running' => FetchTask::where('source_api', $sourceApi)->where('status', 'running')->count(),
            'rerunning' => FetchTask::where('source_api', $sourceApi)->where('status', 'rerunning')->count(),
            'completed' => FetchTask::where('source_api', $sourceApi)->where('status', 'completed')->count(),
            'total' => FetchTask::where('source_api', $sourceApi)->count(),
        ];

        $this->line("📈 Stats: Total={$stats['total']}, Pending={$stats['pending']}, Running={$stats['running']}, Rerunning={$stats['rerunning']}, Completed={$stats['completed']}");
    }
}