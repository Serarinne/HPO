<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\FetchedWallpaper;
use App\Models\Wallpaper;
use App\Jobs\ProcessFetchedWallpaperJob; 

class FetchedWallpaperController extends Controller
{
    const SIMILARITY_THRESHOLD = 80;

    public function index(Request $request)
    {
        $searchQuery = $request->input('search');
        $statusFilter = $request->input('status', 'pending');
        $ratingFilter = $request->input('rating');
        $sourceFilter = $request->input('source', 'all');

        $fetchQuery = FetchedWallpaper::query();

        if ($searchQuery) {
            $cleanSearch = preg_replace('/[+\-><()~*"@]+/', ' ', $searchQuery);
            $words = array_filter(explode(' ', trim($cleanSearch)));
            $booleanSearch = '';

            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    $booleanSearch .= '+' . $word . '* ';
                }
            }

            if (empty($booleanSearch)) {
                if (is_numeric($searchQuery)) {
                    $fetchQuery->where('id', $searchQuery);
                } else {
                    $fetchQuery->whereRaw('1 = 0');
                }
            } else {
                $fetchQuery->whereRaw(
                    "MATCH(tags, characters, artists) AGAINST(? IN BOOLEAN MODE)",
                    [$booleanSearch]
                );
            }
        }

        $fetchQuery->when($statusFilter, function ($q, $status) {
            if ($status === 'all_mod') {
                $q->whereIn('status', ['pending', 'processing', 'failed']);
            } elseif (in_array($status, ['pending', 'imported', 'failed', 'processing', 'rejected', 'duplicate'])) {
                $q->where('status', $status);
            } else {
                $q->where('status', 'pending');
            }
        });

        $fetchQuery->when($ratingFilter, function ($q, $rating) {
            if (in_array($rating, ['general', 'sensitive', 'questionable', 'explicit', 'unknown'])) {
                $q->where('rating', $rating);
            }
        });

        $fetchQuery->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) {
            $q->where('source_api', $sourceFilter);
        });

        $fetchedWallpapers = $fetchQuery->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $md5s = $fetchedWallpapers->pluck('md5_booru')->filter()->toArray();
        $existingMD5 = [];

        if (!empty($md5s)) {
            $existingMD5 = Wallpaper::whereIn('md5_booru', $md5s)
                ->pluck('md5_booru')
                ->toArray();
        }

        $fetchedWallpapers->getCollection()->transform(function ($item) use ($existingMD5) {
            $item->is_duplicate = in_array($item->md5_booru, $existingMD5);
            return $item;
        });

        $fetchCount = FetchedWallpaper::where('status', 'pending')->count();

        return view('fetched.index', [
            'fetchedWallpapers' => $fetchedWallpapers,
            'fetchCount' => $fetchCount,
            'currentRating' => $ratingFilter,
            'currentStatus' => $statusFilter,
            'currentSearch' => $searchQuery,
            'currentSource' => $sourceFilter,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $fetch = FetchedWallpaper::findOrFail($id);

        if ($request->has('force') && $request->force == 'true') {
            return $this->dispatchApprovalJob($fetch);
        }

        try {
            $isAbsoluteDuplicate = false;

            if (!empty($fetch->md5_booru) && Wallpaper::where('md5_booru', $fetch->md5_booru)->exists()) {
                $isAbsoluteDuplicate = true;
            } elseif (!empty($fetch->source_api) && !empty($fetch->source_id)) {
                $isAbsoluteDuplicate = Wallpaper::where('source_api', $fetch->source_api)
                    ->where('source_id', $fetch->source_id)
                    ->exists();
            }

            if ($isAbsoluteDuplicate) {
                $fetch->update(['status' => 'duplicate']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'The data was automatically marked as duplicate because the MD5 or source ID already exists in the system.',
                    'new_status' => 'duplicate',
                ]);
            }

            $duplicates = $this->checkSimilarity($fetch);

            if (!empty($duplicates)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Warning: ' . count($duplicates) . ' similar images were found in the database.',
                    'duplicates' => $duplicates,
                ]);
            }

            return $this->dispatchApprovalJob($fetch);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function replace($id, $target_id)
    {
        $fetch = FetchedWallpaper::findOrFail($id);
        $existingWallpaper = Wallpaper::findOrFail($target_id);

        return $this->dispatchApprovalJob($fetch, $existingWallpaper);
    }

    private function checkSimilarity($fetch)
    {
        $duplicates = [];
        $matchedIds = [];

        if (!empty($fetch->source_url)) {
            $sourceMatches = Wallpaper::where('source_url', $fetch->source_url)->limit(5)->get();

            foreach ($sourceMatches as $match) {
                $duplicates[] = [
                    'id' => $match->id,
                    'slug' => $match->slug,
                    'thumbnail_url' => $match->thumbnail_webp ?? $match->thumbnail,
                    'width' => $match->width,
                    'height' => $match->height,
                    'distance' => '100% (Same Source URL)',
                ];

                $matchedIds[] = $match->id;
            }
        }

        $sourceApi = $fetch->source_api;
        $rawArtists = is_array($fetch->artists) ? $fetch->artists : json_decode($fetch->artists, true) ?? [];
        $rawChars = is_array($fetch->characters) ? $fetch->characters : json_decode($fetch->characters, true) ?? [];
        $rawTags = is_array($fetch->tags) ? $fetch->tags : json_decode($fetch->tags, true) ?? [];

        $artistIds = $this->findExistingIds($rawArtists, 'artist_api_tags', 'artist_id', $sourceApi, 'artists');
        $characterIds = $this->findExistingIds($rawChars, 'character_api_tags', 'character_id', $sourceApi, 'characters');

        $limitedTags = array_slice($rawTags, 0, 15);
        $tagIds = $this->findExistingIds($limitedTags, 'tag_api_tags', 'tag_id', $sourceApi, 'tags');

        $totalWeight = (count($artistIds) * 20) + (count($characterIds) * 10) + count($tagIds);

        if ($totalWeight >= 10) {
            $minScore = ($totalWeight * self::SIMILARITY_THRESHOLD) / 100;
            $queries = [];
            $bindings = [];

            if (!empty($artistIds)) {
                $placeholders = implode(',', array_fill(0, count($artistIds), '?'));
                $queries[] = "SELECT wallpaper_id, 20 as weight FROM wallpaper_artist WHERE artist_id IN ($placeholders)";
                $bindings = array_merge($bindings, $artistIds);
            }

            if (!empty($characterIds)) {
                $placeholders = implode(',', array_fill(0, count($characterIds), '?'));
                $queries[] = "SELECT wallpaper_id, 10 as weight FROM wallpaper_character WHERE character_id IN ($placeholders)";
                $bindings = array_merge($bindings, $characterIds);
            }

            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $queries[] = "SELECT wallpaper_id, 1 as weight FROM wallpaper_tag WHERE tag_id IN ($placeholders)";
                $bindings = array_merge($bindings, $tagIds);
            }

            if (!empty($queries)) {
                $unionQuery = implode(' UNION ALL ', $queries);

                $excludeSql = '';
                if (!empty($matchedIds)) {
                    $excludePlaceholders = implode(',', array_fill(0, count($matchedIds), '?'));
                    $excludeSql = " WHERE wallpaper_id NOT IN ($excludePlaceholders) ";
                    $bindings = array_merge($bindings, $matchedIds);
                }

                $sql = "
                    SELECT wallpaper_id, SUM(weight) as total_score
                    FROM ($unionQuery) as matches
                    $excludeSql
                    GROUP BY wallpaper_id
                    HAVING total_score >= ?
                    ORDER BY total_score DESC
                    LIMIT 10
                ";

                $bindings[] = $minScore;
                $contextMatches = DB::select($sql, $bindings);

                if (!empty($contextMatches)) {
                    $contextIds = array_column($contextMatches, 'wallpaper_id');
                    $scores = array_column($contextMatches, 'total_score', 'wallpaper_id');
                    $candidates = Wallpaper::whereIn('id', $contextIds)->get();

                    foreach ($candidates as $candidate) {
                        $score = $scores[$candidate->id];
                        $similarityPercent = min(100, round(($score / $totalWeight) * 100));

                        $duplicates[] = [
                            'id' => $candidate->id,
                            'slug' => $candidate->slug,
                            'thumbnail_url' => $candidate->thumbnail['webp'] ?? $candidate->thumbnail['jpg'],
                            'width' => $candidate->width,
                            'height' => $candidate->height,
                            'distance' => "{$similarityPercent}% (Tag Match)",
                        ];
                    }
                }
            }
        }

        usort($duplicates, function ($a, $b) {
            $scoreA = (int) filter_var($a['distance'], FILTER_SANITIZE_NUMBER_INT);
            $scoreB = (int) filter_var($b['distance'], FILTER_SANITIZE_NUMBER_INT);

            return $scoreB <=> $scoreA;
        });

        return $duplicates;
    }

    private function dispatchApprovalJob($fetch, $existingWallpaper = null)
    {
        $rawArtists = is_array($fetch->artists) ? $fetch->artists : json_decode($fetch->artists, true) ?? [];
        $rawChars = is_array($fetch->characters) ? $fetch->characters : json_decode($fetch->characters, true) ?? [];
        $rawTags = is_array($fetch->tags) ? $fetch->tags : json_decode($fetch->tags, true) ?? [];

        $artistIds = $this->resolveIdsWithAutoCreate($rawArtists, 'artist', $fetch->source_api);
        $characterIds = $this->resolveIdsWithAutoCreate($rawChars, 'character', $fetch->source_api);
        $tagIds = $this->resolveIdsWithAutoCreate($rawTags, 'tag', $fetch->source_api);

        $videoFormats = ['mp4', 'webm', 'video/mp4', 'video/webm'];

        if (in_array(strtolower($fetch->file_type), $videoFormats)) {
            $tagIds[] = 3470;
            $tagIds = array_unique($tagIds);
        }

        $finalKeywords = $this->generateAggregatedKeywords($artistIds, $characterIds, $tagIds);

        if (in_array(strtolower($fetch->file_type), $videoFormats)) {
            $existingKeywords = array_map('trim', explode(',', strtolower($finalKeywords)));

            if (!in_array('live wallpaper', $existingKeywords)) {
                $finalKeywords = !empty($finalKeywords) ? $finalKeywords . ', live wallpaper' : 'live wallpaper';
            }
        }

        DB::beginTransaction();

        try {
            $wallpaperData = [
                'source_url' => $fetch->source_url,
                'source_id' => $fetch->source_id,
                'source_api' => $fetch->source_api,
                'md5_booru' => $fetch->md5_booru,
                'rating' => $fetch->rating ?? 'unknown',
                'keywords' => $finalKeywords,
                'width' => $fetch->width ?? 0,
                'height' => $fetch->height ?? 0,
                // 'user_id' => auth()->id() ?? 1,
                'user_id' => 1,
            ];

            if (!$existingWallpaper) {
                $existingWallpaper = Wallpaper::create($wallpaperData);
            } else {
                $existingWallpaper->update($wallpaperData);
            }

            $existingWallpaper->artists()->sync($artistIds);
            $existingWallpaper->characters()->sync($characterIds);
            $existingWallpaper->tags()->sync($tagIds);

            $fetch->update(['status' => 'processing']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save metadata in the controller: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save data to the database.',
            ], 500);
        }

        // ProcessFetchedWallpaperJob::dispatch(
        //     $fetch->id,
        //     $existingWallpaper->id
        // );

        $request = new \Illuminate\Http\Request([
            'fetchedId' => $fetch->id,
            'targetId' => $existingWallpaper->id
        ]);

        app(\App\Http\Controllers\ProcessFetchedWallpaperController::class)->process($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Approved! Metadata has been saved and the system is processing the file in the background...',
            'new_status' => 'processing',
        ]);
    }

    private function generateAggregatedKeywords($artistIds, $characterIds, $tagIds)
    {
        $allKeywords = [];

        if (!empty($artistIds)) {
            $artists = DB::table('artists')->whereIn('id', $artistIds)->select('name', 'keywords')->get();

            foreach ($artists as $a) {
                $allKeywords[] = $a->name;
                if ($a->keywords) {
                    $allKeywords[] = $a->keywords;
                }
            }
        }

        if (!empty($characterIds)) {
            $chars = DB::table('characters')->whereIn('id', $characterIds)->select('name', 'keywords')->get();

            foreach ($chars as $c) {
                $allKeywords[] = $c->name;
                if ($c->keywords) {
                    $allKeywords[] = $c->keywords;
                }
            }
        }

        if (!empty($tagIds)) {
            $tags = DB::table('tags')->whereIn('id', $tagIds)->select('name', 'keywords')->get();

            foreach ($tags as $t) {
                $allKeywords[] = $t->name;
                if ($t->keywords) {
                    $allKeywords[] = $t->keywords;
                }
            }
        }

        $flatList = [];

        foreach ($allKeywords as $k) {
            $parts = explode(',', $k);

            foreach ($parts as $p) {
                $clean = trim($p);
                if (!empty($clean)) {
                    $flatList[] = $clean;
                }
            }
        }

        $flatList = array_unique(array_map('strtolower', $flatList));

        return implode(', ', $flatList);
    }

    private function resolveIdsWithAutoCreate(array $rawNames, string $type, string $sourceApi): array
    {
        if (empty($rawNames)) {
            return [];
        }

        $resolvedIds = [];
        $rawNames = array_map('trim', $rawNames);

        switch ($type) {
            case 'artist':
                $mainTable = 'artists';
                $apiTable = 'artist_api_tags';
                $foreignKey = 'artist_id';
                break;
            case 'character':
                $mainTable = 'characters';
                $apiTable = 'character_api_tags';
                $foreignKey = 'character_id';
                break;
            case 'tag':
            default:
                $mainTable = 'tags';
                $apiTable = 'tag_api_tags';
                $foreignKey = 'tag_id';
                break;
        }

        foreach ($rawNames as $rawName) {
            $existingId = DB::table($apiTable)
                ->where('source_api', $sourceApi)
                ->where('tag_name', $rawName)
                ->value($foreignKey);

            if ($existingId) {
                $resolvedIds[] = $existingId;
                continue;
            }

            $cleanName = str_replace('_', ' ', $rawName);

            if ($type !== 'tag') {
                $cleanName = ucwords($cleanName);
            }

            $slug = Str::slug($cleanName);

            $mainRecord = DB::table($mainTable)
                ->where('slug', $slug)
                ->orWhere('name', $cleanName)
                ->first();

            $finalId = null;

            if ($mainRecord) {
                $finalId = $mainRecord->id;
            } else {
                try {
                    $finalId = DB::table($mainTable)->insertGetId([
                        'name' => ucwords($cleanName),
                        'slug' => $slug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $finalId = DB::table($mainTable)->where('slug', $slug)->value('id');
                }
            }

            if ($finalId) {
                $exists = DB::table($apiTable)
                    ->where('source_api', $sourceApi)
                    ->where('tag_name', $rawName)
                    ->exists();

                if (!$exists) {
                    $apis = in_array($sourceApi, ['danbooru', 'gelbooru']) 
                        ? ['danbooru', 'gelbooru'] 
                        : [$sourceApi];

                    $payload = array_map(fn($api) => [
                        $foreignKey  => $finalId,
                        'source_api' => $api,
                        'tag_name'   => $rawName,
                    ], $apis);

                    DB::table($apiTable)->insert($payload);
                }

                $resolvedIds[] = $finalId;
            }
        }

        return array_unique($resolvedIds);
    }

    private function findExistingIds(array $rawNames, string $apiTable, string $foreignKey, string $sourceApi, string $mainTable): array
    {
        if (empty($rawNames)) {
            return [];
        }

        $rawNames = array_map('trim', $rawNames);

        $idsFromApi = DB::table($apiTable)
            ->where('source_api', $sourceApi)
            ->whereIn('tag_name', $rawNames)
            ->pluck($foreignKey)
            ->toArray();

        $normalizedNames = [];

        foreach ($rawNames as $name) {
            $normalizedNames[] = str_replace('_', ' ', $name);

            $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', '', str_replace('_', ' ', $name)));
            if (!empty($cleanName) && $cleanName !== $name) {
                $normalizedNames[] = $cleanName;
            }
        }

        $idsFromMain = [];

        if (!empty($normalizedNames)) {
            $idsFromMain = DB::table($mainTable)
                ->whereIn('name', $normalizedNames)
                ->pluck('id')
                ->toArray();
        }

        return array_unique(array_merge($idsFromApi, $idsFromMain));
    }

    public function reject($id)
    {
        $fetch = FetchedWallpaper::findOrFail($id);
        $fetch->update(['status' => 'duplicate']);

        return response()->json(['status' => 'success', 'message' => 'Item marked as rejected.']);
    }

    public function destroy($id)
    {
        $fetch = FetchedWallpaper::findOrFail($id);
        $fetch->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Item marked as rejected (not deleted).']);
    }
}