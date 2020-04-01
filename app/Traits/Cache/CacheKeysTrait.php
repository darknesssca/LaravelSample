<?php


namespace App\Traits\Cache;


trait CacheKeysTrait
{
    protected function getCacheKey(...$args): string
    {
        return $this->generateCacheKey('cache|key', $args);
    }

    protected function generateCacheKey($prefix, ...$args): string
    {
        if (substr($prefix, -1) != '|') {
            $prefix .= '|';
        }
        return $prefix . md5(serialize($args));
    }
}
