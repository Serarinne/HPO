<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStorage
{
    protected $storageZone;
    protected $apiKey;
    protected $region;

    public function __construct($storageZone = null, $apiKey = null, $region = null)
    {
        $this->storageZone = $storageZone;
        $this->apiKey = $apiKey;
        $this->region = $region;
    }

    private function getBaseUrl()
    {
        if ($this->region) {
            return "https://{$this->region}.storage.bunnycdn.com";
        }

        return "https://storage.bunnycdn.com";
    }

    public function upload($path, $content)
    {
        $url = "{$this->getBaseUrl()}/{$this->storageZone}/{$path}";

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-Type' => 'application/octet-stream',
        ])
        ->withBody($content, 'application/octet-stream')
        ->put($url);

        if ($response->failed()) {
            throw new \Exception("BunnyCDN Upload Failed: " . $response->body());
        }

        return true;
    }

    public function delete($path)
    {
        $url = "{$this->getBaseUrl()}/{$this->storageZone}/{$path}";

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->delete($url);

        if ($response->failed() && $response->status() != 404) {
            Log::error("BunnyCDN Delete Failed: " . $response->body());
            return false;
        }

        return true;
    }

    public function exists($path)
    {
        $url = "{$this->getBaseUrl()}/{$this->storageZone}/{$path}";

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->get($url);

        return $response->successful();
    }
}