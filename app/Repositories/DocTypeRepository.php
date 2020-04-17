<?php


namespace App\Repositories;


use App\Cache\DocTypeCacheTag;
use App\Contracts\Repositories\DocTypeRepositoryContract;
use App\Models\DocType;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class DocTypeRepository implements DocTypeRepositoryContract
{
    use DocTypeCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getDocTypesList()
    {
        $cacheTag = self::getDocTypeTag();
        $cacheKey = self::getDocTypeListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return DocType::select(["id", "code", "name"])->get();
        });
    }

    public function getCompanyDocTypeByCode($code, $companyId)
    {
        $cacheTag = self::getDocTypeTag();
        $cacheKey = self::getCacheKey($code, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($code, $companyId) {
            return DocType::with([
                'codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }
            ])->where('code', $code)->first();
        });
    }

    public function getPassportCode($isRussian)
    {
        $codes = $this->getDocTypeRelations();
        if ($isRussian) {
            return isset($codes['passport']['russian']) ? $codes['passport']['russian'] : null;
        }
        return isset($codes['passport']['foreign']) ? $codes['passport']['foreign'] : null;
    }

    public function getLicenseCode($isRussian)
    {
        $codes = $this->getDocTypeRelations();
        if ($isRussian) {
            return isset($codes['license']['russian']) ? $codes['license']['russian'] : null;
        }
        return isset($codes['license']['foreign']) ? $codes['license']['foreign'] : null;
    }

    public function getCarDocCode($type)
    {
        $codes = $this->getDocTypeRelations();
        return isset($codes['car'][$type]) ? $codes['car'][$type] : null;
    }

    public function getInspectionCode($isRussian)
    {
        $codes = $this->getDocTypeRelations();
        if ($isRussian) {
            return isset($codes['inspection']['russian']) ? $codes['inspection']['russian'] : null;
        }
        return isset($codes['inspection']['foreign']) ? $codes['inspection']['foreign'] : null;
    }

    public function getDocTypeRelations()
    {
        return [
            'passport' => [
                'russian' => 'RussianPassport',
                'foreign' => 'ForeignPassport',
            ],
            'license' => [
                'russian' => 'DriverLicense',
                'foreign' => 'ForeignDriverLicense',
            ],
            'car' => [
                'pts' => 'pts',
                'sts' => 'sts',
            ],
            'inspection' => [
                'russian' => 'Inspection',
                'foreign' => 'ForeignInspection',
            ],
        ];
    }
}
