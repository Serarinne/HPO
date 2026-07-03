<?php

namespace App\Http\Controllers;

use App\Models\Wallpaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use App\Services\BunnyStorage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\FileExtension;

class WallpaperController extends Controller
{
    public function index(Request $request)
    {
        $query = Wallpaper::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('id', $search)
                    ->orWhere('keywords', 'like', '%' . $search . '%');
            });
        });

        $query->when($request->filled('rating'), function ($q) use ($request) {
            $q->whereIn('rating', $request->input('rating'));
        });

        $query->when($request->filled('seo'), function ($q) use ($request) {
            $seoFilters = $request->input('seo');

            $q->where(function ($subQuery) use ($seoFilters) {
                if (in_array('filled', $seoFilters)) {
                    $subQuery->whereNotNull('seo_title')->where('seo_title', '!=', '');
                }

                if (in_array('empty', $seoFilters)) {
                    $method = in_array('filled', $seoFilters) ? 'orWhere' : 'where';
                    $subQuery->$method(function ($emptyQ) {
                        $emptyQ->whereNull('seo_title')->orWhere('seo_title', '');
                    });
                }
            });
        });

        $query->when($request->filled('workflow'), function ($q) use ($request) {
            $workflowFilters = $request->input('workflow');

            $q->where(function ($subQuery) use ($workflowFilters) {
                if (in_array('debug', $workflowFilters)) {
                    $subQuery->where('debug', 1);
                }

                if (in_array('ready', $workflowFilters)) {
                    $method = in_array('debug', $workflowFilters) ? 'orWhere' : 'where';
                    $subQuery->$method('debug', 0);
                }
            });
        });

        $wallpapers = $query->select('id', 'thumbnail', 'height', 'width', 'views_count', 'debug', 'rating', 'file_type')
            ->latest('id')
            ->paginate(32);

        return view('wallpapers.index', compact('wallpapers'));
    }

    public function edit($id)
    {
        $wallpaper = Wallpaper::with(['tags', 'characters', 'artists'])->findOrFail($id);

        $preloadedTags = $wallpaper->tags->map(fn ($t) => ['id' => $t->id, 'text' => $t->name]);
        $preloadedChars = $wallpaper->characters->map(fn ($c) => ['id' => $c->id, 'text' => $c->name]);
        $preloadedArtists = $wallpaper->artists->map(fn ($a) => ['id' => $a->id, 'text' => $a->name]);

        return view('wallpapers.edit', compact(
            'wallpaper',
            'preloadedTags',
            'preloadedChars',
            'preloadedArtists'
        ));
    }

    public function update(Request $request, int $id)
    {
        $wallpaper = Wallpaper::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'required|in:general,sensitive,questionable,explicit,unknown',
            'source_url' => 'nullable|url',
            'slug' => 'nullable|string|max:255|unique:wallpapers,slug,' . $id,
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'image_alt' => 'nullable|string',
            'image_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'tags' => 'array',
            'characters' => 'array',
            'artists' => 'array',
        ]);

        if (isset($validated['slug']) && $validated['slug'] !== $wallpaper->slug) {
            $validated['created_at'] = now();
            $validated['updated_at'] = now();
        }

        $wallpaper->update($validated);

        if (isset($validated['tags'])) {
            $wallpaper->tags()->sync($validated['tags']);
        }

        if (isset($validated['characters'])) {
            $wallpaper->characters()->sync($validated['characters']);
        }

        if (isset($validated['artists'])) {
            $wallpaper->artists()->sync($validated['artists']);
        }

        $wallpaper->refresh();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Wallpaper updated successfully.',
                'updated_at' => $wallpaper->updated_at->diffForHumans(),
                'slug_changed' => $wallpaper->wasChanged('slug'),
            ]);
        }

        return redirect()->route('wallpapers.edit', $id)
            ->with('success', 'Wallpaper updated successfully.');
    }

    public function reupload(Request $request, $id)
    {
        $request->validate([
            'file_url' => 'required|url',
        ]);

        set_time_limit(0);

        Log::info("[Reupload] Starting reupload for Wallpaper ID: {$id} from URL: {$request->file_url}");

        $wallpaper = Wallpaper::findOrFail($id);
        $downloadUrl = $request->file_url;

        $bunnyConfig = config('services.bunny');

        if (!$bunnyConfig || empty($bunnyConfig['api_key'])) {
            return response()->json(['message' => 'BunnyCDN configuration not found.'], 500);
        }

        $bunny = new BunnyStorage(
            $bunnyConfig['storage_name'],
            $bunnyConfig['api_key'],
            $bunnyConfig['region'] ?? null
        );

        $tempDir = storage_path('app/temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempId = uniqid('reupload_');
        $tempFiles = [];

        try {
            DB::beginTransaction();

            $localTempPath = $tempDir . '/' . $tempId . '_temp';
            $tempFiles[] = $localTempPath;

            $response = Http::withoutVerifying()
                ->withOptions([
                    'sink' => $localTempPath,
                    'verify' => false,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CloudFlare-AlwaysOnline/1.0; +http://www.cloudflare.com/always-online)',
                ])
                ->timeout(600)
                ->get($downloadUrl);

            if (!$response->successful() || !file_exists($localTempPath) || filesize($localTempPath) === 0) {
                throw new \Exception('Failed to download media from URL.');
            }

            $fileSize = filesize($localTempPath);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($localTempPath);
            $isVideo = str_starts_with($mimeType, 'video/');

            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
            ];

            $ext = $extMap[$mimeType] ?? ($isVideo ? 'mp4' : 'jpg');

            $finalWidth = 0;
            $finalHeight = 0;

            $filename = $wallpaper->id;
            $paddedId = str_pad($filename, 9, '0', STR_PAD_LEFT);
            $shardPath = implode('/', str_split($paddedId, 3));

            $originalPathDB = "original/{$shardPath}/{$filename}.{$ext}";
            $previewPathDB = "preview/{$shardPath}/{$filename}" . ($isVideo ? '.mp4' : '');
            $thumbPathDB = "thumbnail/{$shardPath}/{$filename}";

            if ($isVideo) {
                $ffmpeg = FFMpeg::create([
                    'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
                    'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
                    'timeout' => 3600,
                    'ffmpeg.threads' => 4,
                ]);

                $ffprobe = FFProbe::create([
                    'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
                    'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
                ]);

                $videoStream = $ffprobe->streams($localTempPath)->videos()->first();
                $finalWidth = $videoStream->get('width');
                $finalHeight = $videoStream->get('height');

                $localStaticPath = $tempDir . '/' . $tempId . '_static.jpg';
                $tempFiles[] = $localStaticPath;

                $video = $ffmpeg->open($localTempPath);
                $video->frame(TimeCode::fromSeconds(1))->save($localStaticPath);

                $localPreviewVideoPath = $tempDir . '/' . $tempId . '_preview.mp4';
                $tempFiles[] = $localPreviewVideoPath;

                $video->filters()->resize(new Dimension(1280, 720), \FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_HEIGHT);
                $format = new X264();
                $format->setKiloBitrate(1000);
                $format->setAudioCodec('aac');
                $video->save($format, $localPreviewVideoPath);

                $bunny->upload($originalPathDB, file_get_contents($localTempPath));
                $bunny->upload($previewPathDB, file_get_contents($localPreviewVideoPath));
                $this->processThumbnailImage($localStaticPath, $bunny, $thumbPathDB);
            } else {
                $img = Image::decodePath($localTempPath);
                $finalWidth = $img->width();
                $finalHeight = $img->height();
                unset($img);

                $bunny->upload($originalPathDB, file_get_contents($localTempPath));
                $this->processPreviewImage($localTempPath, $bunny, $previewPathDB);
                $this->processThumbnailImage($localTempPath, $bunny, $thumbPathDB);
            }

            $wallpaper->update([
                'width' => $finalWidth,
                'height' => $finalHeight,
                'file_size' => $fileSize,
                'file_type' => $mimeType,
                'type' => $mimeType,
                'original' => $originalPathDB,
                'thumbnail' => $thumbPathDB,
                'preview' => $previewPathDB,
                'source_url' => $downloadUrl,
                'updated_at' => now(),
            ]);

            DB::commit();
            Log::info("[Reupload] Successfully processed Wallpaper ID: {$id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Media successfully re-fetched!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Reupload] Failed to process Wallpaper ID {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to re-fetch media: ' . $e->getMessage(),
            ], 500);
        } finally {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function processPreviewImage($filePath, $bunny, $basePath)
    {
        $img = Image::decodePath($filePath);
        $prev = ($img->width() > 1200) ? $img->scaleDown(width: 1200) : $img;

        $bunny->upload("{$basePath}.webp", (string) $prev->encode(new WebpEncoder(quality: 80)));
        $bunny->upload("{$basePath}.jpg", (string) $prev->encodeUsingFileExtension(FileExtension::JPG, progressive: true, quality: 80));

        unset($img, $prev);
    }

    private function processThumbnailImage($filePath, $bunny, $basePath)
    {
        $img = Image::decodePath($filePath);
        $thumb = $img->scaleDown(width: 600);

        $bunny->upload("{$basePath}.webp", (string) $thumb->encode(new WebpEncoder(quality: 75)));
        $bunny->upload("{$basePath}.jpg", (string) $thumb->encodeUsingFileExtension(FileExtension::JPG, progressive: true, quality: 75));

        unset($img, $thumb);
    }

    public function delete(int $id)
    {
        $wallpaper = Wallpaper::findOrFail($id);

        $wallpaper->delete();

        return redirect()->route('wallpapers.index')
            ->with('success', 'Wallpaper has been permanently deleted.');
    }

    public function toggleDebug(Request $request, $id)
    {
        $wallpaper = Wallpaper::findOrFail($id);
        $wallpaper->debug = $request->boolean('debug');
        $wallpaper->save();

        return response()->json([
            'success' => true,
            'message' => 'Debug status has been updated.',
            'rating' => $wallpaper->rating,
        ]);
    }

    public function updateRating(Request $request, $id)
    {
        $wallpaper = Wallpaper::findOrFail($id);
        $wallpaper->rating = $request->rating;
        $wallpaper->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating has been updated.',
            'rating' => $wallpaper->rating,
        ]);
    }
}