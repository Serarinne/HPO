<?php

namespace App\Models\Concerns;

trait HasStorageUrl
{
    protected function getCdnHostname(): string
    {
        static $hostname = null;

        if ($hostname === null) {
            $hostname = config('app.storage_url', '');
        }

        return $hostname;
    }

    protected function getCdnUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return "{$this->getCdnHostname()}/" . ltrim($path, '/');
    }

    private function resolveImageUrl(string $columnName, string $targetExtension, string $defaultImage): string
    {
        $value = $this->getRawOriginal($columnName);

        if (empty($value)) {
            return $this->getCdnUrl($defaultImage);
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $currentExt = pathinfo($value, PATHINFO_EXTENSION);

        if (!empty($currentExt)) {
            return $this->getCdnUrl($value);
        }

        return $this->getCdnUrl($value . '.' . $targetExtension);
    }
}