<?php

namespace App\Services;

use App\Models\FetchedWallpaper;
use App\Models\Wallpaper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use Exception;

class WallpaperFetcherService
{
    private function getUserAgent()
    {
        return 'Mozilla/5.0 (compatible; CloudFlare-AlwaysOnline/1.0; +http://www.cloudflare.com/always-online)';
    }

    private function getZerochanCookies()
    {
        return [
            'z_id'      => config('services.zerochan.z_id'),
            'z_hash'    => config('services.zerochan.z_hash'),
            'z_theme'   => config('services.zerochan.z_theme'),
            'PHPSESSID' => config('services.zerochan.phpsessid'),
            'xbotcheck' => config('services.zerochan.xbotcheck'),
        ];
    }

    private function safeDateTimeParse($dateString)
    {
        if (empty($dateString)) return now();
        try {
            return Carbon::parse($dateString, 'UTC')->setTimezone(config('app.timezone', 'UTC'));
        } catch (Exception $e) {
            return now();
        }
    }

    /**
     * Memanggil API berdasarkan sumber
     */
    public function fetchFromApi($api, $tag, $page)
    {
        try {
            if ($api === 'danbooru') {
                $url = "https://danbooru.donmai.us/posts.json";
                $params = ['tags' => $tag, 'limit' => 100, 'page' => $page];

                if (config('services.danbooru.username') && config('services.danbooru.api_key')) {
                    $params['login'] = config('services.danbooru.username');
                    $params['api_key'] = config('services.danbooru.api_key');
                }

                $response = Http::withHeaders(['User-Agent' => $this->getUserAgent()])
                    ->timeout(20)
                    ->get($url, $params);

                return $response->successful() ? $response->json() : null;
            }

            if ($api === 'gelbooru') {
                $url = config('services.gelbooru.base_url', "https://gelbooru.com/index.php");
                $params = [
                    'page' => 'dapi', 's' => 'post', 'q' => 'index', 'json' => 1,
                    'tags' => $tag, 'limit' => 50, 'pid' => max(0, $page - 1)
                ];

                if (config('services.gelbooru.user_id') && config('services.gelbooru.api_key')) {
                    $params['user_id'] = config('services.gelbooru.user_id');
                    $params['api_key'] = config('services.gelbooru.api_key');
                }

                $response = Http::withHeaders(['User-Agent' => $this->getUserAgent()])
                    ->timeout(25)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['post'] ?? (is_array($data) ? $data : []);
                }
                return null;
            }

            if ($api === 'zerochan') {
                $url = "https://www.zerochan.net/" . urlencode($tag);
                $response = Http::withCookies($this->getZerochanCookies(), 'www.zerochan.net')
                    ->withHeaders(['User-Agent' => $this->getUserAgent()])
                    ->timeout(20)
                    ->get($url, ['p' => $page, 'l' => 20, 'json' => 1]);

                if ($response->successful()) {
                    $json = $response->json();
                    return $json['items'] ?? $json ?? [];
                }
                return null;
            }
        } catch (Exception $e) {
            Log::error("[API] {$api} Exception: " . $e->getMessage());
            return null;
        }

        return [];
    }

    /**
     * Parsing post date based on API source
     */
    public function parsePostDate($post, $api) {
        try {
            if ($api === 'danbooru' || $api === 'gelbooru') {
                return Carbon::parse($post['created_at']);
            }
            if ($api === 'zerochan') {
                return isset($post['created_at']) ? Carbon::parse($post['created_at']) : now();
            }
        } catch (Exception $e) {
            return now();
        }
        return now();
    }

    /**
     * Map data and save using updateOrCreate
     */
    public function processAndSave($post, $sourceApi)
    {
        $mappedData = null;

        if ($sourceApi === 'zerochan') $mappedData = $this->scrapeZerochanDetail($post);
        elseif ($sourceApi === 'danbooru') $mappedData = $this->mapDanbooru($post);
        elseif ($sourceApi === 'gelbooru') $mappedData = $this->mapGelbooru($post);

        if (!$mappedData) return 'skipped';

        try {
            $model = FetchedWallpaper::updateOrCreate(
                [
                    'source_api' => $mappedData['source_api'],
                    'source_id'  => $mappedData['source_id'],
                ],
                $mappedData
            );

            return $model->wasRecentlyCreated ? 'saved' : 'updated';
        } catch (Exception $e) {
            // Tangkap exception duplikat md5_booru (23000) yang bersumber dari unique DB constraints
            if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                return 'duplicate_fetched';
            }
            Log::error("Save error: " . $e->getMessage());
            return 'error';
        }
    }

    private function mapDanbooru($post) {
        if (!isset($post['file_url']) || !isset($post['md5'])) return null;
        
        $ratingMap = ['g'=>'general','s'=>'sensitive','q'=>'questionable','e'=>'explicit'];
        
        $sourceUrl = $post['source'] ?? null;
        if (!empty($post['pixiv_id'])) {
            $sourceUrl = 'https://www.pixiv.net/artworks/' . $post['pixiv_id'];
        } elseif ($sourceUrl && str_contains($sourceUrl, 'pximg.net')) {
            if (preg_match('/\/(\d+)(_p\d+)?\./', $sourceUrl, $matches)) {
                $sourceUrl = 'https://www.pixiv.net/artworks/' . $matches[1];
            }
        }

        return [
            'original'   => $post['file_url'],
            'preview'    => $post['large_file_url'] ?? $post['file_url'],
            'thumbnail'  => $post['preview_file_url'] ?? null,
            'tags'       => isset($post['tag_string_general']) ? array_values(array_filter(explode(' ', $post['tag_string_general']))) : [],
            'characters' => isset($post['tag_string_character']) ? array_values(array_filter(explode(' ', $post['tag_string_character']))) : [],
            'artists'    => isset($post['tag_string_artist']) ? array_values(array_filter(explode(' ', $post['tag_string_artist']))) : [],
            'rating'     => $ratingMap[strtolower($post['rating'] ?? 'q')] ?? 'questionable',
            'width'      => $post['image_width'] ?? 0,
            'height'     => $post['image_height'] ?? 0,
            'file_size'  => $post['file_size'] ?? 0,
            'file_type'  => $post['file_ext'] ?? null,
            'md5_booru'  => $post['md5'],
            'source_api' => 'danbooru',
            'source_id'  => (string)$post['id'],
            'source_url' => $sourceUrl,
            'created_at' => $this->safeDateTimeParse($post['created_at'] ?? null),
            'updated_at' => $this->safeDateTimeParse($post['updated_at'] ?? null)
        ];
    }

    private function mapGelbooru($post) {
        if (!isset($post['file_url']) || !isset($post['md5'])) return null;
        
        $ratingMap = ['g'=>'general','s'=>'sensitive','q'=>'questionable','e'=>'explicit'];
        
        return [
            'original'   => $post['file_url'],
            'preview'    => $post['sample_url'] ?? $post['file_url'],
            'thumbnail'  => $post['preview_url'] ?? null,
            'tags'       => isset($post['tags']) ? array_values(array_filter(explode(' ', $post['tags']))) : [],
            'characters' => [],
            'artists'    => [],
            'rating'     => $ratingMap[strtolower($post['rating'] ?? 'q')] ?? 'questionable',
            'width'      => $post['width'] ?? 0,
            'height'     => $post['height'] ?? 0,
            'file_size'  => 0,
            'file_type'  => pathinfo($post['file_url'], PATHINFO_EXTENSION),
            'md5_booru'  => $post['md5'],
            'source_api' => 'gelbooru',
            'source_id'  => (string)$post['id'],
            'source_url' => "https://gelbooru.com/index.php?page=post&s=view&id=" . $post['id'],
            'created_at' => $this->safeDateTimeParse($post['created_at'] ?? null),
            'updated_at' => $this->safeDateTimeParse($post['updated_at'] ?? null)
        ];
    }

    private function scrapeZerochanDetail($basicPost)
    {
        $id = $basicPost['id'] ?? null;
        if (!$id) return null;

        $url = "https://www.zerochan.net/" . $id;

        try {
            $response = Http::withCookies($this->getZerochanCookies(), 'www.zerochan.net')
                ->withHeaders(['User-Agent' => $this->getUserAgent()])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) return null;

            $html = $response->body();
            $crawler = new Crawler($html);

            $originalUrl = null;
            try {
                $originalUrl = $crawler->filter('#large > a.preview')->attr('href');
            } catch (Exception $e) {
                try {
                   $originalUrl = $crawler->filter('meta[property="og:image"]')->attr('content');
                } catch (Exception $e2) {}
            }

            if (!$originalUrl) return null; 

            $md5 = $basicPost['md5'] ?? null;
            if (!$md5) {
                try {
                    $metaText = $crawler->filter('#menu')->text(); 
                    if (preg_match('/MD5:\s*([a-f0-9]{32})/', $metaText, $matches)) {
                        $md5 = $matches[1];
                    }
                } catch (Exception $e) {}
            }

            $sourceUrl = $basicPost['source'] ?? null;
            if (!$sourceUrl) {
                try {
                    $sourceUrl = $crawler->filter('#menu li')->reduce(function (Crawler $node) {
                        return str_contains($node->text(), 'Source:');
                    })->filter('a')->attr('href');
                } catch (Exception $e) {}
            }

            $tags = []; $artists = []; $characters = [];
            $crawler->filter('ul#tags li')->each(function (Crawler $node) use (&$tags, &$artists, &$characters) {
                $tagName = trim($node->filter('a')->text());
                $class = $node->attr('class'); 
                if (str_contains($class, 'mangaka')) $artists[] = $tagName;
                elseif (str_contains($class, 'character')) $characters[] = $tagName;
                else $tags[] = $tagName; 
            });

            return [
                'original'   => $originalUrl,
                'preview'    => $basicPost['thumbnail'] ?? $originalUrl,
                'thumbnail'  => $basicPost['thumbnail'] ?? null,
                'tags'       => array_values(array_unique($tags)),
                'characters' => array_values(array_unique($characters)),
                'artists'    => array_values(array_unique($artists)),
                'rating'     => 'questionable',
                'width'      => $basicPost['width'] ?? 0,
                'height'     => $basicPost['height'] ?? 0,
                'file_size'  => 0,
                'file_type'  => pathinfo($originalUrl, PATHINFO_EXTENSION),
                'md5_booru'  => $md5,
                'source_api' => 'zerochan',
                'source_id'  => (string) $id,
                'source_url' => $sourceUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ];

        } catch (Exception $e) {
            Log::error("[Zerochan] Scraping Error ID {$id}: " . $e->getMessage());
            return null;
        }
    }
}