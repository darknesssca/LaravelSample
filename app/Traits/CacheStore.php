<?php


namespace App\Traits;


use Illuminate\Support\Facades\Cache;

trait CacheStore
{
    public function exist(string $id): bool
    {
        return Cache::has($id);
    }

    public function put(string $id, array $data = null, int $lifetime = 3600):void
    {
        Cache::put($id, $data, $lifetime);
    }

    public function get(string $id): ?array
    {
        $result = $this->look($id);
        $this->flush($id);
        return $result;
    }

    public function look(string $id): ?array
    {
        if ($this->exist($id)) {
            return Cache::get($id);
        }

        return null;
    }

    public function flush(string $id): void
    {
        if ($this->exist($id)) {
            Cache::forget($id);
        }
    }

    public function getId(...$args)
    {
        return 'temp_data|' . md5(serialize($args));
    }
}
