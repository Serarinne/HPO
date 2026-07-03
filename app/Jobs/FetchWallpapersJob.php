<?php

namespace App\Jobs;

use App\Models\FetchTask;
use App\Services\WallpaperFetcherService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchWallpapersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $taskId;

    public const PAGE_LIMIT_PER_RUN = 5;
    public const MAX_CONSECUTIVE_DUPLICATE_PAGES = 5;

    public int $timeout = 300;
    public int $tries = 2;
    public int $backoff = 60;
    public bool $failOnTimeout = true;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("fetch-task:{$this->taskId}"))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(WallpaperFetcherService $fetcherService): void
    {
        $task = FetchTask::find($this->taskId);

        if (!$task) {
            Log::error("[Fetch Job] [Task:{$this->taskId}] Task tidak ditemukan.");
            return;
        }

        if (!in_array($task->status, ['running', 'rerunning'], true)) {
            Log::warning("[Fetch Job] [Task:{$task->id}] [Tag:{$task->tag_name}] Status invalid: {$task->status}. Skipping.");
            return;
        }

        $rawTag = trim($task->tag_name);
        $apiTag = $task->source_api !== 'zerochan'
            ? strtolower(str_replace(' ', '_', $rawTag))
            : $rawTag;

        $isFullRun = $task->status === 'running';
        $isRerun = $task->status === 'rerunning';
        $mode = $isRerun ? 'INCREMENTAL' : 'FULL';

        Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] START | API: {$task->source_api} | Page: {$task->current_page}");

        $lastRecordedDate = $task->last_post_date;

        if ($isFullRun && (!$lastRecordedDate || (int) $lastRecordedDate->year === 1970)) {
            $lastRecordedDate = Carbon::now()->subDays(30);
            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Initial barrier: {$lastRecordedDate->toDateTimeString()}");
        }

        // Variabel untuk melacak tanggal dan ID terbaru di sesi Job ini
        $newestDateInSession = $task->last_post_date;
        $highestSourceIdInSession = $task->last_source_id; 

        $shouldComplete = false;
        $consecutiveDuplicatePages = 0;
        $totalItemsAdded = 0;
        $totalItemsUpdated = 0;
        $totalItemsSkipped = 0;

        for ($i = 0; $i < self::PAGE_LIMIT_PER_RUN; $i++) {
            $task->refresh();

            if (!in_array($task->status, ['running', 'rerunning'], true)) {
                Log::warning("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Status berubah menjadi {$task->status}. Stop.");
                return;
            }

            $task->last_run_at = now();
            $task->save();

            $currentPage = (int) $task->current_page;
            $itemsAdded = 0;
            $itemsUpdated = 0;
            $itemsSkipped = 0;

            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Fetching page {$currentPage}...");

            $posts = $fetcherService->fetchFromApi($task->source_api, $apiTag, $currentPage);

            if ($posts === null) {
                Log::warning("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Page {$currentPage}: API error. Stop for now.");
                break;
            }

            if (empty($posts)) {
                Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Page {$currentPage}: Empty response. End of data.");
                $shouldComplete = true;
                break;
            }

            $pageHasNewerContent = false;

            foreach ($posts as $post) {
                $postDate = $fetcherService->parsePostDate($post, $task->source_api);
                
                // Ambil ID dari post (menyesuaikan apakah array atau object)
                $postId = is_array($post) ? ($post['id'] ?? 0) : ($post->id ?? 0);

                // Update pelacak Tanggal terbaru
                if (!$newestDateInSession || $postDate->gt($newestDateInSession)) {
                    $newestDateInSession = $postDate;
                }

                // Update pelacak ID terbaru
                if ($postId && (!$highestSourceIdInSession || $postId > $highestSourceIdInSession)) {
                    $highestSourceIdInSession = $postId;
                }

                if ($lastRecordedDate ? $postDate->gt($lastRecordedDate) : true) {
                    $pageHasNewerContent = true;
                }

                $saveStatus = $fetcherService->processAndSave($post, $task->source_api);

                if ($saveStatus === 'saved') {
                    $itemsAdded++;
                } elseif ($saveStatus === 'updated') {
                    $itemsUpdated++;
                } else {
                    $itemsSkipped++;
                }
            }

            $totalItemsAdded += $itemsAdded;
            $totalItemsUpdated += $itemsUpdated;
            $totalItemsSkipped += $itemsSkipped;

            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Page {$currentPage}: Added {$itemsAdded}, Updated {$itemsUpdated}, Skipped {$itemsSkipped}");

            if ($isRerun) {
                $isOldDuplicatePage = ($itemsAdded === 0 && $itemsUpdated === 0 && !$pageHasNewerContent);

                if ($isOldDuplicatePage) {
                    $consecutiveDuplicatePages++;
                    Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Duplicate old page ({$consecutiveDuplicatePages}/" . self::MAX_CONSECUTIVE_DUPLICATE_PAGES . ")");
                } else {
                    $consecutiveDuplicatePages = 0;
                }

                if ($consecutiveDuplicatePages >= self::MAX_CONSECUTIVE_DUPLICATE_PAGES) {
                    Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Early stop due duplicate page limit.");
                    $shouldComplete = true;
                    break;
                }
            }

            $task->current_page = $currentPage + 1;
            $task->last_run_at = now();
            $task->save();

            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] Advance to next page: {$task->current_page}");

            sleep(1);
        }

        $task->refresh();

        // --- SIMPAN CHECKPOINT TERBARU KE DATABASE ---
        $needsUpdate = false;

        if ($newestDateInSession && (!$task->last_post_date || $newestDateInSession->gt($task->last_post_date))) {
            $task->last_post_date = $newestDateInSession;
            $needsUpdate = true;
        }

        if ($highestSourceIdInSession && (!$task->last_source_id || $highestSourceIdInSession > $task->last_source_id)) {
            $task->last_source_id = $highestSourceIdInSession;
            $needsUpdate = true;
        }

        if ($shouldComplete) {
            $task->status = 'completed';
            $task->current_page = 1;
            $needsUpdate = true;

            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] COMPLETED | Added {$totalItemsAdded} | Updated {$totalItemsUpdated} | Skipped {$totalItemsSkipped}");
        } else {
            Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] CONTINUE | Next Page {$task->current_page} | Added {$totalItemsAdded} | Updated {$totalItemsUpdated} | Skipped {$totalItemsSkipped}");
        }

        if ($needsUpdate || $task->isDirty('last_run_at')) {
            $task->last_run_at = null;
            $task->save();
        }

        Log::info("[Fetch Job] [{$mode}] [Task:{$task->id}] [Tag:{$rawTag}] END | Final Status: {$task->status} | Current Page: {$task->current_page}");
    }

    public function failed(Throwable $exception): void
    {
        // ... (Kode failed tidak berubah)
        Log::error("[Fetch Job] [Task:{$this->taskId}] FAILED | {$exception->getMessage()}");

        try {
            DB::transaction(function () {
                $task = FetchTask::where('id', $this->taskId)
                    ->lockForUpdate()
                    ->first();

                if (!$task) {
                    Log::warning("[Fetch Job] [Task:{$this->taskId}] Rollback skipped: task not found.");
                    return;
                }

                $previousStatus = $task->status;

                $rollbackStatus = match ($task->status) {
                    'running' => 'pending',
                    'rerunning' => 'completed',
                    default => $task->status,
                };

                $task->status = $rollbackStatus;
                $task->last_run_at = null;
                $task->save();

                Log::warning("[Fetch Job] [Task:{$task->id}] [Tag:{$task->tag_name}] Rollback after failure: {$previousStatus} -> {$rollbackStatus}");
            }, 3);
        } catch (Throwable $rollbackException) {
            Log::error("[Fetch Job] [Task:{$this->taskId}] Rollback failed | {$rollbackException->getMessage()}");
        }
    }
}