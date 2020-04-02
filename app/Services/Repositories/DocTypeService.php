<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class DocTypeService implements DocTypeServiceContract
{
    use CacheTrait;

    protected $docTypeRepository;

    public function __construct(
        DocTypeRepositoryContract $docTypeRepository
    )
    {
        $this->docTypeRepository = $docTypeRepository;
    }

    public function getDocTypesList()
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->docTypeRepository->getDocTypesList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCompanyDocTypeByCode($code, $companyId)
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $codes = $data->codes;
        if (!$codes || !$codes->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->codes->first()->reference_doctype_code;
    }

    public function getCompanyPassportDocType($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getPassportCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyLicenseDocType($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getLicenseCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyCarDocType($type, $companyId)
    {
        $code = $this->docTypeRepository->getCarDocCode($type);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyInspectionDocType($companyId)
    {
        $code = $this->docTypeRepository->getInspectionCode();
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, $companyId);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code, $companyId){
            return $this->docTypeRepository->getCompanyDocTypeByCode($code, $companyId);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyDocTypeByRelation($relationCode, $type, $companyId)
    {
        switch ($relationCode) {
            case 'passport':
                return $this->getCompanyPassportDocType($type, $companyId);
            case 'license':
                return $this->getCompanyLicenseDocType($type, $companyId);
            case 'car':
                return $this->getCompanyCarDocType($type, $companyId);
            case 'inspection':
                return $this->getCompanyInspectionDocType($companyId);
            default:
                throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }
}
