<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Repositories\UsageTargetRepositoryContract;
use App\Exceptions\GuidesNotFoundException;

class UsageTargetService implements UsageTargetServiceContract
{
    protected $usageTargetRepository;

    public function __construct(UsageTargetRepositoryContract $usageTargetRepository)
    {
        $this->usageTargetRepository = $usageTargetRepository;
    }

    public function getUsageTargetList()
    {
        $data = $this->usageTargetRepository->getUsageTargetList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }

    public function getCompanyUsageTarget($id, $companyId)
    {
        $data = $this->usageTargetRepository->getCompanyUsageTarget($id, $companyId);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->codes->first()->reference_usage_target_code;
    }

    public function getCompanyUsageTarget2($id, $companyId)
    {
        $tag = $this->getGuidesUsageTargetsTag();
        $key = $this->getCacheKey($tag, $id, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($id, $companyId){
            return $this->usageTargetRepository->getCompanyUsageTarget($id, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_usage_target_code2;
    }
}
