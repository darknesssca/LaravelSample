<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Exceptions\GuidesNotFoundException;
use App\Traits\LocalStorageTrait;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class InsuranceCompanyService implements InsuranceCompanyServiceContract
{
    use LocalStorageTrait, CacheTrait;

    protected $insuranceCompanyRepository;

    public function __construct(InsuranceCompanyRepositoryContract $insuranceCompanyRepository)
    {
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;
    }

    public function getCompany($code)
    {
        if ($this->isStored($code)) {
            return $this->load($code);
        }
        $tag = $this->getGuidesInsuranceCompaniesTag();
        $key = $this->getCacheKey($tag, 'code', $code);
        $object = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code){
            return $this->insuranceCompanyRepository->getCompany($code);
        });
        $this->save($code, $object);
        return $object;
    }

    public function getCompanyById($id)
    {
        if ($this->isStored($id)) {
            return $this->load($id);
        }
        $tag = $this->getGuidesInsuranceCompaniesTag();
        $key = $this->getCacheKey($tag, 'id', $id);
        $object = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($id){
            return $this->insuranceCompanyRepository->getCompanyById($id);
        });
        $this->save($id, $object);
        return $object;
    }

    public function getInsuranceCompanyList()
    {
        $tag = $this->getGuidesInsuranceCompaniesTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->insuranceCompanyRepository->getInsuranceCompanyList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
