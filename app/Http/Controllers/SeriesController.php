<?php

namespace App\Http\Controllers;

use App\Models\Series;
use App\Models\Wallpaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Services\BunnyStorage;
use Illuminate\Support\Facades\Log;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        
        $ratings = $request->input('rating', []);
        $seos = $request->input('seo', []);
        $workflows = $request->input('workflow', []);
        $characters = $request->input('character', []);

        $series = Series::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when(!empty($ratings), fn ($q) => $q->whereIn('rating', $ratings))
            ->when(!empty($seos) && count($seos) === 1, function ($q) use ($seos) {
                if (in_array('empty', $seos)) {
                    $q->where(fn($sub) => $sub->whereNull('seo_title')->orWhere('seo_title', ''));
                } elseif (in_array('filled', $seos)) {
                    $q->whereNotNull('seo_title')->where('seo_title', '!=', '');
                }
            })
            ->when(!empty($workflows), fn ($q) => $q->whereIn('debug', $workflows))
            ->when(!empty($characters) && count($characters) === 1, function ($q) use ($characters) {
                if (in_array('has_character', $characters)) {
                    $q->has('characters');
                } elseif (in_array('no_character', $characters)) {
                    $q->doesntHave('characters');
                }
            })
            ->latest('id')
            ->paginate(32)
            ->withQueryString();

        return view('series.index', compact('series', 'search'));
    }

    public function edit($id)
    {
        $series = Series::with(['parents', 'apiTags'])->findOrFail($id);
        
        $preloadParents = $series->parents->map(fn($p) => ['id' => $p->id, 'text' => $p->name]);

        return view('series.edit', compact('series', 'preloadParents'));
    }

    public function update(Request $request, $id)
    {
        $series = Series::findOrFail($id);

        $imageFile = $request->file('image');

        if ($request->has('api_tags')) {
            $filteredTags = array_filter($request->api_tags, function ($tag) {
                return !empty($tag['source_api']) && !empty($tag['tag_name']);
            });
            $request->merge([
                'api_tags' => array_values($filteredTags)
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique($series->getConnectionName() . '.series')->ignore($series->id),
            ],
            'keywords' => 'nullable|string',
            'description' => 'nullable|string',
            'rating' => 'required|in:general,sensitive,questionable,explicit,unknown',
            'debug' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'parents' => 'nullable|array',
            'parents.*' => 'exists:' . $series->getConnectionName() . '.series,id',
            'api_tags' => 'nullable|array',
            'api_tags.*.source_api' => 'required_with:api_tags|string',
            'api_tags.*.tag_name' => 'required_with:api_tags|string',
        ]);

        $bunny = $this->getBunnyStorage();
        if (!$bunny) {
            return response()->json([
                'success' => false,
                'message' => 'System configuration error (BunnyCDN).'
            ], 500);
        }

        DB::beginTransaction();

        $newImagePath = null;
        $oldImagePath = $series->image;

        try {
            if ($imageFile && $imageFile->isValid()) {
                $newImagePath = $this->handleImageUpload($series->id, $imageFile, $bunny);
                $series->image = $newImagePath;
            }

            $series->fill([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug'] ?? $validated['name']),
                'keywords' => $validated['keywords'] ?? null,
                'description' => $validated['description'] ?? null,
                'rating' => $validated['rating'],
                'debug' => $request->boolean('debug'),
                'seo_keywords' => $validated['meta_keywords'] ?? null,
                'seo_title' => $validated['meta_title'] ?? null,
                'seo_description' => $validated['meta_description'] ?? null,
            ]);

            $series->save();

            if (isset($validated['parents'])) {
                $series->parents()->sync($validated['parents']);
            } else {
                $series->parents()->detach();
            }

            $series->apiTags()->delete();

            if (!empty($validated['api_tags'])) {
                $series->apiTags()->createMany($validated['api_tags']);
            }

            DB::commit();

            if ($imageFile && $imageFile->isValid() && $oldImagePath && $bunny) {
                try {
                    $bunny->delete($oldImagePath . '.webp');
                    $bunny->delete($oldImagePath . '.jpg');
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old image: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Series updated successfully.',
                'new_image' => $series->image_url,
                'updated_at' => $series->updated_at->format('d M Y'),
                'slug' => $series->slug
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($newImagePath && $bunny) {
                try {
                    $bunny->delete($newImagePath . '.webp');
                    $bunny->delete($newImagePath . '.jpg');
                } catch (\Exception $deleteEx) {
                    Log::critical('Failed to rollback orphaned new image in BunnyCDN: ' . $deleteEx->getMessage());
                }
            }

            Log::error('Series Update Error: ' . $e->getMessage(), [
                'series_id' => $series->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to update series. Please try again.'
            ], 500);
        }
    }

    public function delete($id)
    {
        $series = Series::findOrFail($id);
        
        $series->delete();

        return redirect()->route('series.index')->with('success', 'Series successfully deleted.');
    }

    public function toggleDebug(Request $request, $id)
    {
        $series = Series::findOrFail($id);
        $series->debug = $request->boolean('debug');
        $series->save();

        return response()->json([
            'success' => true,
            'message' => 'Debug successfully updated.',
            'rating' => $series->rating
        ]);
    }

    public function updateRating(Request $request, $id)
    {
        $series = Series::findOrFail($id);
        $series->rating = $request->rating;
        $series->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating successfully updated.',
            'rating' => $series->rating
        ]);
    }

    public function list(Request $request)
    {
        $query = Series::query();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $data = $query->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->name,
                ];
            });

        return response()->json($data);
    }

    private function getBunnyStorage()
    {
        $bunnyConfig = config("services.bunny");

        if (!$bunnyConfig || empty($bunnyConfig['api_key'])) {
            Log::error("BunnyCDN API Key not found for app");
            return null;
        }

        return new BunnyStorage(
            $bunnyConfig['storage_name'] ?? $bunnyConfig['storage_zone'], 
            $bunnyConfig['api_key'],
            $bunnyConfig['region'] ?? null
        );
    }

    private function handleImageUpload($seriesId, $file, BunnyStorage $bunny)
    {
        if (!$file || !$file->isValid()) {
            throw new \RuntimeException('Invalid uploaded file.');
        }

        $tmpPath = $file->getPathname();
        if (!$tmpPath || !is_file($tmpPath)) {
            throw new \RuntimeException('Uploaded file temp path is invalid.');
        }

        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            throw new \RuntimeException('Uploaded file is not a readable image.');
        }

        [$origWidth, $origHeight] = $imageInfo;

        $paddedId = str_pad($seriesId, 9, '0', STR_PAD_LEFT);
        $shardPath = implode('/', str_split($paddedId, 3));
        $basePath = "series/{$shardPath}/{$seriesId}";

        $isPortrait = $origHeight > $origWidth;

        if ($isPortrait) {
            $targetWidth = 600;
            $targetHeight = null;
        } else {
            $targetWidth = null;
            $targetHeight = 600;
        }

        $webpContent = $this->resizeImageAspectRatio($tmpPath, $targetWidth, $targetHeight, 'webp');
        $bunny->upload($basePath . '.webp', $webpContent);

        $jpgContent = $this->resizeImageAspectRatio($tmpPath, $targetWidth, $targetHeight, 'jpg');
        $bunny->upload($basePath . '.jpg', $jpgContent);

        return $basePath;
    }

    private function resizeImageAspectRatio(string $tmpPath, $targetWidth, $targetHeight, $format = 'webp')
    {
        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            throw new \RuntimeException('Image file cannot be read.');
        }

        [$origWidth, $origHeight, $type] = $imageInfo;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($tmpPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($tmpPath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($tmpPath);
                break;
            default:
                throw new \RuntimeException('Unsupported image type.');
        }

        $aspectRatio = $origWidth / $origHeight;

        if ($targetWidth === null && $targetHeight !== null) {
            $newHeight = $targetHeight;
            $newWidth = round($targetHeight * $aspectRatio);
        } elseif ($targetHeight === null && $targetWidth !== null) {
            $newWidth = $targetWidth;
            $newHeight = round($targetWidth / $aspectRatio);
        } else {
            $newWidth = $targetWidth ?? $origWidth;
            $newHeight = $targetHeight ?? $origHeight;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled(
            $destination,
            $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        ob_start();

        if ($format === 'jpg' || $format === 'jpeg') {
            $whiteBg = imagecreatetruecolor($newWidth, $newHeight);
            imagefill($whiteBg, 0, 0, imagecolorallocate($whiteBg, 255, 255, 255));
            imagecopy($whiteBg, $destination, 0, 0, 0, 0, $newWidth, $newHeight);
            imagejpeg($whiteBg, null, 90);
            imagedestroy($whiteBg);
        } else {
            imagewebp($destination, null, 90);
        }

        $content = ob_get_clean();

        imagedestroy($source);
        imagedestroy($destination);

        return $content;
    }
}