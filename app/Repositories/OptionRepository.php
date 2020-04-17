<?php


namespace App\Repositories;


use App\Cache\OptionCacheTag;
use App\Models\Option;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class OptionRepository
{
    use OptionCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    /**
     * @param integer $id
     * @return Option
     */
    public function getById($id)
    {
        $id = intval($id);

        if ($id <= 0){
            throw new InvalidArgumentException('Указан не корректный id');
        }

        $cacheTag = self::getOptionCacheTag();
        $cacheKey = self::getCacheKey($id);

        $option = Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id) {
           return Option::find($id);
        });

        if (empty($option)){
            throw new ModelNotFoundException(sprintf('Не найдены настройки с id %s', $id));
        }

        return $option;
    }

    /**
     * @param string $code
     * @return Option
     */
    public function getByCode($code)
    {
        $cacheTag = self::getOptionCacheTag();
        $cacheKey = self::getCacheKey("code", $code);

        $option = Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($code) {
          return Option::where('code', $code)->first();
        });

        if (empty($option)){
            throw new ModelNotFoundException(sprintf('Не найдены настройки с кодом %s', $code));
        }

        return $option;
    }

    /**
     * @return Option[]|Collection
     */
    public function getAll()
    {
        $cacheTag = self::getOptionCacheTag();
        $cacheKey = self::getOptionListKey();

        $option = Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return Option::all();
        });

        if (empty($option)){
            throw new ModelNotFoundException('Настройки не найдены');
        }

        return $option;
    }
}
