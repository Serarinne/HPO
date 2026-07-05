<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkFetchedWallpapersAsImported extends Command
{
    protected $signature = 'wallpapers:mark-imported 
                            {--chunk=1000 : Jumlah data per chunk}
                            {--dry-run : Hanya simulasi tanpa update}';

    protected $description = 'Update status fetched_wallpapers menjadi imported jika source_url ada di tabel wallpapers';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize < 1) {
            $this->error('Nilai --chunk harus lebih dari 0.');
            return self::FAILURE;
        }

        $totalScanned = 0;
        $totalMatched = 0;
        $totalUpdated = 0;

        $this->info("Memulai proses dengan chunk size: {$chunkSize}");

        DB::table('fetched_wallpapers')
            ->select('id', 'source_api', 'status')
            ->whereNotNull('source_api')
            ->where('status', '!=', 'imported')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$totalScanned, &$totalMatched, &$totalUpdated) {
                $totalScanned += $rows->count();

                $sourceUrls = $rows->pluck('source_api')
                    ->filter()
                    ->unique()
                    ->values();

                if ($sourceUrls->isEmpty()) {
                    return;
                }

                $matchedSourceUrls = DB::table('wallpapers')
                    ->whereIn('source_api', $sourceUrls)
                    ->pluck('source_api');

                if ($matchedSourceUrls->isEmpty()) {
                    $this->line("Scanned: {$totalScanned} | Matched: {$totalMatched} | Updated: {$totalUpdated}");
                    return;
                }

                $matchedIds = $rows
                    ->whereIn('source_api', $matchedSourceUrls)
                    ->pluck('id')
                    ->values();

                $totalMatched += $matchedIds->count();

                if ($this->option('dry-run')) {
                    $this->line("Scanned: {$totalScanned} | Matched: {$totalMatched} | Updated: {$totalUpdated} [dry-run]");
                    return;
                }

                $updated = DB::table('fetched_wallpapers')
                    ->whereIn('id', $matchedIds)
                    ->update([
                        'status' => 'imported',
                        'updated_at' => now(),
                    ]);

                $totalUpdated += $updated;

                $this->line("Scanned: {$totalScanned} | Matched: {$totalMatched} | Updated: {$totalUpdated}");
            }, 'id');

        $this->newLine();
        $this->info("Selesai. Total scanned: {$totalScanned}, matched: {$totalMatched}, updated: {$totalUpdated}");

        return self::SUCCESS;
    }
}