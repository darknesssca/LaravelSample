<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Repositories\UsageTargetRepositoryContract;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class UsageTargetService implements UsageTargetServiceContract
{
    use CacheTrait;

    protected $usageTargetRepository;

    public function __construct(
        UsageTargetRepositoryContract $usageTargetRepository
    )
    {
        $this->usageTargetRepository = $usageTargetRepository;
    }

    public function getUsageTargetList()
    {
        $tag = $this->getGuidesUsageTargetsTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->usageTargetRepository->getUsageTargetList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
