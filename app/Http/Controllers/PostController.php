<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PostCategory;
use Illuminate\Support\Str;
use App\Services\BunnyStorage;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->has('seo') && is_array($request->seo)) {
            $seoFilters = $request->seo;

            $query->where(function ($q) use ($seoFilters) {
                if (in_array('filled', $seoFilters)) {
                    $q->orWhere(function ($subQ) {
                        $subQ->whereNotNull('keywords')
                             ->where('keywords', '!=', '');
                    });
                }

                if (in_array('empty', $seoFilters)) {
                    $q->orWhere(function ($subQ) {
                        $subQ->whereNull('keywords')
                             ->orWhere('keywords', '');
                    });
                }
            });
        }

        $posts = $query->latest('id')->paginate(12);

        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = DB::table('post_categories')->orderBy('name', 'asc')->get();

        return view('posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'excerpt'        => 'nullable|string',
            'body'           => 'required|string',
            'keywords'       => 'required|string',
            'status'         => 'required|string|in:draft,published',
            'published_at'   => 'nullable|date',
            'categories'     => 'nullable|array',
            'categories.*'   => 'integer|exists:post_categories,id',
        ]);

        $bunny = $this->getBunnyStorage();
        if (!$bunny) {
            return back()
                ->withInput()
                ->with('error', 'System configuration error (BunnyCDN).');
        }

        DB::beginTransaction();

        $uploadedImagePath = null;

        try {
            $baseSlug = $request->filled('slug')
                ? Str::slug($request->slug)
                : Str::slug($request->title);

            if (blank($baseSlug)) {
                $baseSlug = 'post';
            }

            $slug = $baseSlug;
            $counter = 1;

            while (Post::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $post = new Post();
            $post->user_id        = Auth::id() ?? 1;
            $post->title          = $request->title;
            $post->slug           = $slug;
            $post->excerpt        = $request->excerpt;
            $post->body           = $request->body;
            $post->keywords       = $request->keywords;
            $post->status         = $request->status;
            $post->published_at   = $request->published_at;
            $post->featured_image = null;
            $post->save();

            if ($request->hasFile('featured_image') && $request->file('featured_image')->isValid()) {
                $uploadResult = $this->handlePostImageUpload($post->id, $request->file('featured_image'), $bunny);

                $uploadedImagePath = $uploadResult['path'];
                $post->featured_image = $uploadResult['url'];
                $post->save();
            }

            if ($request->filled('categories')) {
                $insertData = [];

                foreach ($request->categories as $categoryId) {
                    $insertData[] = [
                        'post_id'     => $post->id,
                        'category_id' => $categoryId,
                    ];
                }

                DB::table('post_category')->insert($insertData);
            }

            DB::commit();

            return redirect()
                ->route('posts.index')
                ->with('success', 'Post created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($uploadedImagePath && $bunny) {
                try {
                    $bunny->delete($uploadedImagePath);
                } catch (\Exception $deleteEx) {
                    Log::critical('Failed to rollback orphaned post image in BunnyCDN: ' . $deleteEx->getMessage());
                }
            }

            Log::error('Post Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create post: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $categories = DB::table('post_categories')->orderBy('name', 'asc')->get();

        $postCategoryIds = DB::table('post_category')
            ->where('post_id', $id)
            ->pluck('category_id')
            ->toArray();

        // Menyimpan ID kategori dalam bentuk array agar mudah dicek di View
        $post->category_ids = $postCategoryIds;

        return view('posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title'          => 'required|string|max:255',
            'slug'           => 'required|string|max:255|unique:posts,slug,' . $post->id,
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // Diubah menjadi image
            'excerpt'        => 'nullable|string',
            'body'           => 'required|string',
            'keywords'       => 'required|string',
            'status'         => 'required|string|in:draft,published',
            'published_at'   => 'nullable|date',
            'categories'     => 'nullable|array',
            'categories.*'   => 'integer|exists:post_categories,id'
        ]);

        DB::beginTransaction();

        try {
            $post->title        = $request->title;
            $post->slug         = $request->slug;
            $post->excerpt      = $request->excerpt;
            $post->body         = $request->body;
            $post->keywords     = $request->keywords;
            $post->status       = $request->status;
            $post->published_at = $request->published_at;

            // Handle update gambar jika ada file baru yang diunggah
            if ($request->hasFile('featured_image') && $request->file('featured_image')->isValid()) {
                $bunny = $this->getBunnyStorage();
                if (!$bunny) {
                    throw new \Exception('System configuration error (BunnyCDN).');
                }

                $uploadResult = $this->handlePostImageUpload($post->id, $request->file('featured_image'), $bunny);
                $post->featured_image = $uploadResult['url'];
            }

            $post->save();

            // Update relasi kategori
            DB::table('post_category')->where('post_id', $post->id)->delete();

            if ($request->filled('categories')) {
                $insertData = [];
                foreach ($request->categories as $categoryId) {
                    $insertData[] = [
                        'post_id'     => $post->id,
                        'category_id' => $categoryId
                    ];
                }
                DB::table('post_category')->insert($insertData);
            }

            DB::commit();

            return redirect()
                ->route('posts.index')
                ->with('success', 'Post updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Post Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update post: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $post = Post::findOrFail($id);

        DB::table('post_category')->where('post_id', $post->id)->delete();
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post deleted successfully.');
    }

    public function ajaxStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:post_categories,name',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $counter = 1;

        while (PostCategory::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $category = PostCategory::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return response()->json([
            'success' => true,
            'id'      => $category->id,
            'name'    => $category->name,
            'slug'    => $category->slug,
        ]);
    }

    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $bunny = $this->getBunnyStorage();
        if (!$bunny) {
            return response()->json([
                'message' => 'System configuration error (BunnyCDN).'
            ], 500);
        }

        try {
            $file = $request->file('file');

            if (!$file || !$file->isValid()) {
                throw new \RuntimeException('Invalid uploaded file.');
            }

            $uploadResult = $this->handleEditorImageUpload($file, $bunny);

            return response()->json([
                'location' => $uploadResult['url'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Editor Image Upload Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to upload image.',
            ], 422);
        }
    }

    private function getBunnyStorage()
    {
        $bunnyConfig = config('services.bunny');

        if (!$bunnyConfig || empty($bunnyConfig['api_key'])) {
            Log::error('BunnyCDN API Key not found for app');
            return null;
        }

        return new BunnyStorage(
            $bunnyConfig['storage_name'] ?? $bunnyConfig['storage_zone'],
            $bunnyConfig['api_key'],
            $bunnyConfig['region'] ?? null
        );
    }

    private function handlePostImageUpload($postId, $file, BunnyStorage $bunny)
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

        $paddedId = str_pad($postId, 9, '0', STR_PAD_LEFT);
        $shardPath = implode('/', str_split($paddedId, 3));
        $relativePath = "posts/{$shardPath}/{$postId}.webp";

        $isPortrait = $origHeight > $origWidth;

        if ($isPortrait) {
            $targetWidth = 800;
            $targetHeight = null;
        } else {
            $targetWidth = null;
            $targetHeight = 800;
        }

        $webpContent = $this->resizeImageAspectRatio($tmpPath, $targetWidth, $targetHeight, 'webp');
        $bunny->upload($relativePath, $webpContent);

        return [
            'path' => $relativePath,
            'url'  => rtrim(config('services.bunny.cdn_url'), '/') . '/' . ltrim($relativePath, '/'),
        ];
    }

    private function handleEditorImageUpload($file, BunnyStorage $bunny)
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

        $isPortrait = $origHeight > $origWidth;

        if ($isPortrait) {
            $targetWidth = 1200;
            $targetHeight = null;
        } else {
            $targetWidth = null;
            $targetHeight = 1200;
        }

        $relativePath = 'posts/content/' . now()->format('Y/m/d') . '/' . Str::uuid() . '.webp';

        $webpContent = $this->resizeImageAspectRatio($tmpPath, $targetWidth, $targetHeight, 'webp');
        $bunny->upload($relativePath, $webpContent);

        return [
            'path' => $relativePath,
            'url'  => rtrim(config('services.bunny.cdn_url'), '/') . '/' . ltrim($relativePath, '/'),
        ];
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
        imagewebp($destination, null, 90);
        $content = ob_get_clean();

        imagedestroy($source);
        imagedestroy($destination);

        return $content;
    }
}