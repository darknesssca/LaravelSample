<?php


namespace App\Observers;

use App\Cache\DraftCacheTags;
use Benfin\Api\GlobalStorage;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

trait DraftObserver
{
    use DraftCacheTags, CacheKeysTrait;

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($draft) {
            if ($draft->isDirty()) {
                $agentId = GlobalStorage::getUserId();
                Cache::tags(self::getDraftAgentTag($agentId))->flush();
            }
        });

        static::created(function ($draft) {
            $agentId = GlobalStorage::getUserId();
            Cache::tags(self::getDraftAgentTag($agentId))->flush();
        });

        static::deleted(function ($draft) {
            $agentId = GlobalStorage::getUserId();
            Cache::tags(self::getDraftAgentTag($agentId))->flush();
        });
    }
}
