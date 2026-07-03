<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Laravel\Facades\Image;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use App\Models\Wallpaper;
use App\Models\FetchedWallpaper;
use App\Services\BunnyStorage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\FileExtension;
use Throwable;

class ProcessFetchedWallpaperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fetchedId;
    protected $targetId;

    public $timeout = 900;
    public $tries = 2;

    public function __construct($fetchedId, $targetId)
    {
        $this->fetchedId = $fetchedId;
        $this->targetId = $targetId;
    }

    public function handle()
    {
        Log::info("[FetchedJob] Starting file processing for Fetched ID: {$this->fetchedId} Target ID: {$this->targetId}");

        $bunnyConfig = config("services.bunny");

        if (!$bunnyConfig || empty($bunnyConfig['api_key'])) {
            throw new \Exception("BunnyCDN API Key not found in configuration.");
        }

        $bunny = new BunnyStorage(
            $bunnyConfig['storage_name'],
            $bunnyConfig['api_key'],
            $bunnyConfig['region'] ?? null
        );

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

        $tempFiles = [];

        try {
            $fetchedItem = FetchedWallpaper::find($this->fetchedId);
            $wallpaper = Wallpaper::findOrFail($this->targetId);

            if (!$fetchedItem) {
                throw new \Exception("Queue data not found.");
            }

            $downloadUrl = $fetchedItem->original ?? $fetchedItem->source_url;
            if (!$downloadUrl) {
                throw new \Exception("Source URL is empty.");
            }

            $isVideo = in_array(strtolower($fetchedItem->file_type), ['mp4', 'webm', 'video/mp4', 'video/webm']);
            $tempId = uniqid('temp_');

            if ($isVideo) {
                $ext = $fetchedItem->file_type == 'webm' ? 'webm' : 'mp4';
                $localOriginalPath = $tempDir . '/' . $tempId . '_orig.' . $ext;
                $tempFiles[] = $localOriginalPath;

                $this->downloadToFile($downloadUrl, $localOriginalPath);
                $fileSize = filesize($localOriginalPath);

                $localPreviewVideoPath = $tempDir . '/' . $tempId . '_preview.mp4';
                $tempFiles[] = $localPreviewVideoPath;

                $ffmpeg = FFMpeg::create([
                    'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
                    'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
                    'timeout' => 3600,
                    'ffmpeg.threads' => 4,
                ]);

                $video = $ffmpeg->open($localOriginalPath);
                $video->filters()->resize(new Dimension(1280, 720), \FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_HEIGHT);
                $format = new X264();
                $format->setKiloBitrate(1000);
                $format->setAudioCodec("aac");
                $video->save($format, $localPreviewVideoPath);

                $localStaticPath = null;
                $staticImgUrl = $fetchedItem->preview ?? $fetchedItem->thumbnail;
                if ($staticImgUrl) {
                    $localStaticPath = $tempDir . '/' . $tempId . '_static.jpg';
                    $tempFiles[] = $localStaticPath;
                    $this->downloadToFile($staticImgUrl, $localStaticPath);
                }

                $mimeType = 'video/mp4';
            } else {
                $localTempPath = $tempDir . '/' . $tempId . '_temp';
                $tempFiles[] = $localTempPath;

                $this->downloadToFile($downloadUrl, $localTempPath);
                $fileSize = filesize($localTempPath);

                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($localTempPath);
                $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                $ext = $extMap[$mimeType] ?? 'jpg';
            }

            $filename = $wallpaper->id;
            $paddedId = str_pad($filename, 9, '0', STR_PAD_LEFT);
            $shards = str_split($paddedId, 3);
            $shardPath = implode('/', $shards);

            $originalPathDB = "";
            $previewPathDB = "";
            $thumbPathDB = "thumbnail/{$shardPath}/{$filename}";

            if ($isVideo) {
                $originalPathDB = "original/{$shardPath}/{$filename}.{$ext}";
                $bunny->upload($originalPathDB, file_get_contents($localOriginalPath));

                $previewPathDB = "preview/{$shardPath}/{$filename}.mp4";
                $bunny->upload($previewPathDB, file_get_contents($localPreviewVideoPath));

                if (isset($localStaticPath) && file_exists($localStaticPath)) {
                    $this->processThumbnailImage($localStaticPath, $bunny, $thumbPathDB);
                }
            } else {
                $originalPathDB = "original/{$shardPath}/{$filename}.{$ext}";
                $bunny->upload($originalPathDB, file_get_contents($localTempPath));

                $previewPathDB = "preview/{$shardPath}/{$filename}";
                $this->processPreviewImage($localTempPath, $bunny, $previewPathDB);
                $this->processThumbnailImage($localTempPath, $bunny, $thumbPathDB);
            }

            $wallpaper->update([
                'file_size' => $fileSize,
                'file_type' => $mimeType,
                'type' => $mimeType,
                'original' => $originalPathDB,
                'thumbnail' => $thumbPathDB,
                'preview' => $previewPathDB,
                'status' => 'published',
            ]);

            $this->syncDataCounts('artist', $wallpaper->artists()->pluck('artists.id')->toArray());
            $this->syncDataCounts('character', $wallpaper->characters()->pluck('characters.id')->toArray());
            $this->syncDataCounts('tag', $wallpaper->tags()->pluck('tags.id')->toArray());

            $fetchedItem->update(['status' => 'imported']);
            Log::info("[FetchedJob] File processing completed successfully for ID: {$wallpaper->id}");
        } catch (\Exception $e) {
            Log::error("[FetchedJob] Failed processing fetch ID {$this->fetchedId}: " . $e->getMessage());

            $f = FetchedWallpaper::find($this->fetchedId);
            if ($f) {
                $f->update(['status' => 'failed']);
            }

            if (isset($wallpaper)) {
                $wallpaper->update(['status' => 'failed']);
            }

            throw $e;
        } finally {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) @unlink($file);
            }
        }
    }

    private function downloadToFile($url, $savePath)
    {
        $response = Http::withOptions(['sink' => $savePath])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; CloudFlare-AlwaysOnline/1.0; +http://www.cloudflare.com/always-online)'])
            ->timeout(600)
            ->get($url);

        if (!$response->successful() || !file_exists($savePath) || filesize($savePath) === 0) {
            if (file_exists($savePath)) {
                @unlink($savePath);
            }
            throw new \Exception("Failed to download from source: {$url}. HTTP Status: " . $response->status());
        }
    }

    private function processThumbnailImage($filePath, $bunny, $basePath)
    {
        $img = Image::decodePath($filePath);
        $thumb = $img->scaleDown(width: 600);
        $bunny->upload("{$basePath}.webp", (string) $thumb->encode(new WebpEncoder(quality: 75)));
        $bunny->upload("{$basePath}.jpg", (string) $thumb->encodeUsingFileExtension(FileExtension::JPG, progressive: true, quality: 75));
        unset($img, $thumb);
    }

    private function processPreviewImage($filePath, $bunny, $basePath)
    {
        $img = Image::decodePath($filePath);
        $prev = ($img->width() > 1200) ? $img->scaleDown(width: 1200) : $img;
        $bunny->upload("{$basePath}.webp", (string) $prev->encode(new WebpEncoder(quality: 80)));
        $bunny->upload("{$basePath}.jpg", (string) $prev->encodeUsingFileExtension(FileExtension::JPG, progressive: true, quality: 80));
        unset($img, $prev);
    }

    private function syncDataCounts(string $type, array $ids)
    {
        if (empty($ids)) {
            return;
        }

        switch ($type) {
            case 'artist':
                $pivotTable = 'wallpaper_artist';
                $column = 'artist_id';
                break;
            case 'character':
                $pivotTable = 'wallpaper_character';
                $column = 'character_id';
                break;
            case 'tag':
            default:
                $pivotTable = 'wallpaper_tag';
                $column = 'tag_id';
                break;
        }

        $counts = DB::table($pivotTable)
            ->select($column, DB::raw('count(*) as total'))
            ->whereIn($column, $ids)
            ->groupBy($column)
            ->pluck('total', $column)
            ->toArray();

        foreach ($ids as $id) {
            $totalUsage = $counts[$id] ?? 0;
            DB::table('data_counts')->updateOrInsert(
                ['type' => $type, 'data_id' => $id],
                ['total' => $totalUsage, 'updated_at' => now()]
            );
        }
    }
}