<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\BunnyStorage;
use Illuminate\Support\Facades\Log;

class ArtistController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $seos = $request->input('seo', []);
        $workflows = $request->input('workflow', []);
        $hasWallpapers = $request->input('has_wallpaper', []);

        $artists = Artist::query()
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->search($search);
            })
            ->when(!empty($seos) && count($seos) === 1, function (Builder $q) use ($seos) {
                if (in_array('filled', $seos)) {
                    $q->whereNotNull('seo_title')->where('seo_title', '!=', '');
                } elseif (in_array('empty', $seos)) {
                    $q->where(function ($sub) {
                        $sub->whereNull('seo_title')->orWhere('seo_title', '');
                    });
                }
            })
            ->when(!empty($workflows), function (Builder $q) use ($workflows) {
                $q->whereIn('debug', $workflows);
            })
            ->when(!empty($hasWallpapers) && count($hasWallpapers) === 1, function (Builder $q) use ($hasWallpapers) {
                if (in_array('yes', $hasWallpapers)) {
                    $q->has('wallpapers');
                } elseif (in_array('no', $hasWallpapers)) {
                    $q->doesntHave('wallpapers');
                }
            })
            ->indexList()
            ->paginate(32)
            ->withQueryString();

        return view('artists.index', compact('artists'));
    }

    public function edit($id)
    {
        $artist = Artist::with(['links', 'apiTags'])->findOrFail($id);
    
        $socialsData = $artist->links->map(function($link) {
            return [
                'platform' => $link->type,
                'url'      => $link->url
            ];
        })->values()->all();
    
        return view('artists.edit', compact('artist', 'socialsData'));
    }

    public function update(Request $request, $id)
    {
        $artist = Artist::findOrFail($id);

        if ($request->has('api_tags')) {
            $filteredTags = array_filter($request->api_tags, function ($tag) {
                return !empty($tag['source_api']) && !empty($tag['tag_name']);
            });

            $request->merge([
                'api_tags' => array_values($filteredTags)
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('artists')->ignore($artist->id)
            ],
            'keywords' => 'nullable|string',
            'description' => 'nullable|string',
            'debug' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'socials' => 'nullable|array',
            'socials.*.platform' => 'required|string',
            'socials.*.url' => 'required|string',
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
        $oldImagePath = $artist->image;

        try {
            if ($request->hasFile('image')) {
                if (!$request->file('image')->isValid()) {
                    throw new \Exception('The image file is invalid or exceeds the maximum server size limit.');
                }

                $newImagePath = $this->handleImageUpload($artist->id, $request->file('image'), $bunny);
                $artist->image = $newImagePath;
            }

            $artist->fill([
                'name' => $request->name,
                'slug' => Str::slug($request->slug),
                'keywords' => $request->keywords,
                'description' => $request->description,
                'debug' => $request->boolean('debug'),
                'seo_title' => $request->meta_title,
                'seo_description' => $request->meta_description,
                'seo_keywords' => $request->meta_keywords,
            ]);

            $artist->save();

            $artist->links()->delete();

            if ($request->has('socials')) {
                $newLinks = [];

                foreach ($request->socials as $item) {
                    if (!empty($item['url'])) {
                        $newLinks[] = [
                            'type' => $item['platform'],
                            'url'  => $item['url'],
                        ];
                    }
                }

                if (count($newLinks) > 0) {
                    $artist->links()->createMany($newLinks);
                }
            }

            $artist->apiTags()->delete();

            if ($request->has('api_tags') && !empty($request->api_tags)) {
                $artist->apiTags()->createMany($request->api_tags);
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
                'message' => 'Artist updated successfully.',
                'new_image' => $artist->image_url,
                'updated_at' => $artist->updated_at->format('d M Y'),
                'slug' => $artist->slug
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

            Log::error('Artist Update Error: ' . $e->getMessage(), [
                'artist_id' => $artist->id,
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
        $artist = Artist::findOrFail($id);
        $artist->delete();

        return redirect()->route('artists.index')->with('success', 'Artist deleted successfully.');
    }

    public function toggleDebug(Request $request, $id)
    {
        $artist = Artist::findOrFail($id);
        $artist->debug = $request->boolean('debug');
        $artist->save();

        return response()->json([
            'success' => true,
            'message' => 'Debug updated successfully.',
            'rating' => $artist->rating
        ]);
    }

    public function list(Request $request)
    {
        $search = $request->get('q');

        $data = Artist::query()
            ->select('id', 'name')
            ->where('name', 'like', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'text' => $item->name]);

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

    private function handleImageUpload($artistId, $file, BunnyStorage $bunny)
    {
        $paddedId = str_pad($artistId, 9, '0', STR_PAD_LEFT);
        $shardPath = implode('/', str_split($paddedId, 3)); 
        $basePath = "artists/{$shardPath}/{$artistId}";

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
            throw new \Exception("Image file path is empty. Please ensure the file is uploaded correctly.");
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