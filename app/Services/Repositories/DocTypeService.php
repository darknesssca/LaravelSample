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

    public function getDocTypeByCode($code)
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($code){
            return $this->docTypeRepository->getDocTypeByCode($code);
        });
        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->id;
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

    public function getCompanyDocTypeByCode2($code, $companyId)
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, 'alter' ,$companyId);
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
        return $data->codes->first()->reference_doctype_code2;
    }

    public function getCompanyDocTypeByCode3($code, $companyId)
    {
        $tag = $this->getGuidesDocTypesTag();
        $key = $this->getCacheKey($tag, $code, 'alter2' ,$companyId);
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
        return $data->codes->first()->reference_doctype_code3;
    }

    public function getCompanyPassportDocType($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getPassportCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode($code, $companyId);
    }

    public function getCompanyPassportDocType2($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getPassportCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode2($code, $companyId);
    }

    public function getCompanyPassportDocType3($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getPassportCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode3($code, $companyId);
    }

    public function getCompanyLicenseDocType($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getLicenseCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode($code, $companyId);
    }

    public function getCompanyLicenseDocType2($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getLicenseCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode2($code, $companyId);
    }

    public function getCompanyLicenseDocType3($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getLicenseCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode3($code, $companyId);
    }

    public function getCompanyCarDocType($type, $companyId)
    {
        $code = $this->docTypeRepository->getCarDocCode($type);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode($code, $companyId);
    }

    public function getCompanyCarDocType2($type, $companyId)
    {
        $code = $this->docTypeRepository->getCarDocCode($type);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode2($code, $companyId);
    }

    public function getCompanyCarDocType3($type, $companyId)
    {
        $code = $this->docTypeRepository->getCarDocCode($type);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode3($code, $companyId);
    }

    public function getCompanyInspectionDocType($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getInspectionCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode($code, $companyId);
    }

    public function getCompanyInspectionDocType2($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getInspectionCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode2($code, $companyId);
    }

    public function getCompanyInspectionDocType3($isRussian, $companyId)
    {
        $code = $this->docTypeRepository->getInspectionCode($isRussian);
        if (!$code) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $this->getCompanyDocTypeByCode3($code, $companyId);
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
                return $this->getCompanyInspectionDocType($type, $companyId);
            default:
                throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyDocTypeByRelation2($relationCode, $type, $companyId)
    {
        switch ($relationCode) {
            case 'passport':
                return $this->getCompanyPassportDocType2($type, $companyId);
            case 'license':
                return $this->getCompanyLicenseDocType2($type, $companyId);
            case 'car':
                return $this->getCompanyCarDocType2($type, $companyId);
            case 'inspection':
                return $this->getCompanyInspectionDocType2($type,$companyId);
            default:
                throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }

    public function getCompanyDocTypeByRelation3($relationCode, $type, $companyId)
    {
        switch ($relationCode) {
            case 'passport':
                return $this->getCompanyPassportDocType3($type, $companyId);
            case 'license':
                return $this->getCompanyLicenseDocType3($type, $companyId);
            case 'car':
                return $this->getCompanyCarDocType3($type, $companyId);
            case 'inspection':
                return $this->getCompanyInspectionDocType3($type, $companyId);
            default:
                throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
    }
}
