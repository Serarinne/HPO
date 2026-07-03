<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\BunnyStorage;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $ratings = $request->input('rating', []);
        $seos = $request->input('seo', []);
        $workflows = $request->input('workflow', []);
        $hasWallpapers = $request->input('has_wallpaper', []);

        $tags = Tag::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->search($search);
            })
            ->when(!empty($ratings), function ($q) use ($ratings) {
                $q->whereIn('rating', $ratings);
            })
            ->when(!empty($seos) && count($seos) === 1, function ($q) use ($seos) {
                if ($seos[0] === 'filled') {
                    $q->whereNotNull('seo_title')->where('seo_title', '!=', '');
                } elseif ($seos[0] === 'empty') {
                    $q->where(function ($sub) {
                        $sub->whereNull('seo_title')->orWhere('seo_title', '');
                    });
                }
            })
            ->when(!empty($workflows), function ($q) use ($workflows) {
                $q->whereIn('debug', $workflows);
            })
            ->when(!empty($hasWallpapers) && count($hasWallpapers) === 1, function ($q) use ($hasWallpapers) {
                if ($hasWallpapers[0] === 'yes') {
                    $q->has('wallpapers');
                } elseif ($hasWallpapers[0] === 'no') {
                    $q->doesntHave('wallpapers');
                }
            })
            ->latest('id')
            ->paginate(32)
            ->withQueryString();

        return view('tags.index', compact('tags', 'search'));
    }

    public function edit($id)
    {
        $tag = Tag::with('apiTags')->findOrFail($id);

        return view('tags.edit', compact('tag'));
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        if ($request->has('api_tags')) {
            $filteredTags = array_filter($request->api_tags, function ($apiTag) {
                return !empty($apiTag['source_api']) && !empty($apiTag['tag_name']);
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
                Rule::unique('tags')->ignore($tag->id),
            ],
            'keywords' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'api_tags' => 'nullable|array',
            'api_tags.*.source_api' => 'required_with:api_tags|string',
            'api_tags.*.tag_name' => 'required_with:api_tags|string',
            'rating' => 'nullable|in:general,sensitive,questionable,explicit,unknown',
            'debug' => 'nullable|boolean',
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
        $oldImagePath = $tag->image;

        try {
            if ($request->hasFile('image')) {
                if (!$request->file('image')->isValid()) {
                    throw new \Exception('Invalid image file or exceeds the maximum server size limit.');
                }
                
                $newImagePath = $this->handleImageUpload($tag->id, $request->file('image'), $bunny);
                $tag->image = $newImagePath;
            }

            $tag->fill([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug'] ?? $validated['name']),
                'keywords' => $validated['keywords'] ?? null,
                'description' => $validated['description'] ?? null,
                'seo_keywords' => $validated['meta_keywords'] ?? null,
                'seo_title' => $validated['meta_title'] ?? null,
                'seo_description' => $validated['meta_description'] ?? null,
                'rating' => $validated['rating'] ?? $tag->rating,
                'debug' => $request->boolean('debug'),
            ]);

            if (is_null($tag->created_at)) {
                $tag->created_at = now();
            }

            $tag->save();

            $tag->apiTags()->delete();

            if (!empty($validated['api_tags'])) {
                $tag->apiTags()->createMany($validated['api_tags']);
            }

            DB::commit();

            if ($request->hasFile('image') && $oldImagePath && $bunny) {
                try {
                    if (is_array($oldImagePath)) {
                        $oldWebp = $oldImagePath['webp'] ?? null;
                        $oldJpg = $oldImagePath['jpg'] ?? null;

                        if ($oldWebp) {
                            $oldWebpClean = str_replace('/storage/', '', $oldWebp);
                            $bunny->delete($oldWebpClean);
                            
                            if (!$oldJpg) {
                                $oldJpgFallback = preg_replace('/\.(webp)$/i', '.jpg', $oldWebpClean);
                                if ($oldJpgFallback !== $oldWebpClean) {
                                    try { $bunny->delete($oldJpgFallback); } catch (\Exception $ex) {}
                                }
                            }
                        }

                        if ($oldJpg) {
                            $oldJpgClean = str_replace('/storage/', '', $oldJpg);
                            $bunny->delete($oldJpgClean);
                        }
                    } else {
                        $bunny->delete($oldImagePath . '.webp');
                        $bunny->delete($oldImagePath . '.jpg');
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old image from BunnyCDN after update: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully.',
                'new_image' => $tag->image_url,
                'updated_at' => $tag->updated_at->format('d M Y'),
                'slug' => $tag->slug
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

            Log::error('Tag Update Error: ' . $e->getMessage(), [
                'tag_id' => $tag->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return redirect()->route('tags.index')->with('success', 'Tag successfully deleted permanently.');
    }

    public function toggleDebug(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->debug = $request->boolean('debug');
        $tag->save();

        return response()->json([
            'success' => true,
            'message' => 'Debug successfully updated.',
            'rating' => $tag->rating
        ]);
    }

    public function updateRating(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->rating = $request->rating;
        $tag->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating successfully updated.',
            'rating' => $tag->rating
        ]);
    }

    public function list(Request $request)
    {
        $search = $request->get('q');

        $data = Tag::query()
            ->where('name', 'like', "%{$search}%")
            ->limit(20)
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

    private function handleImageUpload($tagId, $file, BunnyStorage $bunny)
    {
        $paddedId = str_pad($tagId, 9, '0', STR_PAD_LEFT);
        $shardPath = implode('/', str_split($paddedId, 3)); 
        $basePath = "tags/{$shardPath}/{$tagId}";

        $targetSize = 600;

        $webpContent = $this->resizeAndCropToSquare($file, $targetSize, 'webp');
        $bunny->upload($basePath . '.webp', $webpContent);

        $jpgContent = $this->resizeAndCropToSquare($file, $targetSize, 'jpg');
        $bunny->upload($basePath . '.jpg', $jpgContent);

        return $basePath;
    }

    private function resizeAndCropToSquare($file, $targetSize, $format = 'webp')
    {
        $filePath = $file->getPathname();
        
        if (empty($filePath)) {
            throw new \Exception("Image file path is empty. Ensure the file is uploaded correctly.");
        }

        list($origWidth, $origHeight, $type) = getimagesize($filePath);
        
        switch ($type) {
            case IMAGETYPE_JPEG: 
                $source = imagecreatefromjpeg($filePath); 
                break;
            case IMAGETYPE_PNG: 
                $source = imagecreatefrompng($filePath); 
                break;
            case IMAGETYPE_WEBP: 
                $source = imagecreatefromwebp($filePath); 
                break;
            default: 
                throw new \Exception("Unsupported image type");
        }

        $minDim = min($origWidth, $origHeight);
        
        $srcX = ($origWidth - $minDim) / 2;
        $srcY = ($origHeight - $minDim) / 2;

        $destination = imagecreatetruecolor($targetSize, $targetSize);

        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $targetSize, $targetSize, $transparent);

        imagecopyresampled(
            $destination, $source,
            0, 0, $srcX, $srcY,
            $targetSize, $targetSize,
            $minDim, $minDim
        );

        ob_start();
        if ($format === 'jpg' || $format === 'jpeg') {
            $whiteBg = imagecreatetruecolor($targetSize, $targetSize);
            imagefill($whiteBg, 0, 0, imagecolorallocate($whiteBg, 255, 255, 255));
            imagecopy($whiteBg, $destination, 0, 0, 0, 0, $targetSize, $targetSize);
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