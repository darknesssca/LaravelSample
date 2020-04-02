<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\GenderRepositoryContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class GenderService implements GenderServiceContract
{
    use CacheTrait;

    protected $genderRepository;

    public function __construct(
        GenderRepositoryContract $genderRepository
    )
    {
        $this->genderRepository = $genderRepository;
    }

    public function getGendersList()
    {
        $tag = $this->getGuidesGendersTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->genderRepository->getGendersList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCompanyGender($id, $companyId)
    {
        $tag = $this->getGuidesGendersTag();
        $key = $this->getCacheKey($tag, $id, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($id, $companyId){
            return $this->genderRepository->getCompanyGender($id, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_gender_code;
    }
}
