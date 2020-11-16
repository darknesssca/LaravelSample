<?php


namespace App\Repositories;


use App\Cache\KaskoTariffTags;
use App\Contracts\Repositories\KaskoTariffRepositoryContract;
use App\Models\KaskoTariff;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class KaskoTariffRepository implements KaskoTariffRepositoryContract
{
    use CacheKeysTrait, KaskoTariffTags;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getList($fields)
    {
        $cacheTag = self::getKaskoTariffTag();
        $cacheKey = self::getCacheKey($fields);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($fields) {
            $query = KaskoTariff::query();

            if (!empty($fields['insurance_company_id'])) {
                $query->where('insurance_company_id', $fields['insurance_company_id']);
            }

            return $query->get();
        });
    }

    public function getActiveTariffs()
    {
        $cacheTag = self::getKaskoTariffTag();
        $cacheKey = self::getCacheKey(true);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return KaskoTariff::query()->whereHas('company', function (Builder $q){
                $q->where('active', true);
            })
                ->where('active', true)
                ->get();
        });
    }

    public function getById($id)
    {
        return KaskoTariff::find($id);
    }

    public function update($id, $data)
    {
        return KaskoTariff::find($id)->update($data);
    }
}
