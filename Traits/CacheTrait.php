<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\CoreBundle\Service\MemcacheService;

trait CacheTrait
{
    /**
     * @var MemcacheService
     */
    protected $memcache;

    /**
     * @required
     * @param MemcacheService $memcache
     */
    public function setMemcache(MemcacheService $memcache): void
    {
        $this->memcache = $memcache;
    }

    /**
     * @param bool $flag
     * @return bool
     */
    public static function addToPrefetchOnSet($flag = true)
    {
        $currentValue = MemcacheService::$addToPrefetchOnSet;
        MemcacheService::$addToPrefetchOnSet = $flag;
        return $currentValue;
    }

    /**
     *
     */
    public static function clearPrefetchedData()
    {
        MemcacheService::$prefetchCacheData = [];
    }

    /**
     * @param      $keys
     * @param null $default
     * @param bool $cached
     */
    public function prefetchDecodedCache($keys, $default = null, $cached = true)
    {
        $result = $this->getMemcache()->getMultiple($keys, $default, $cached);

        if ($result && is_array($result)) {
            foreach ($result as $k => $v) {
                if ($v) {
                    MemcacheService::$prefetchCacheData[$k] = @json_decode($v, true);
                }
            }
        }
    }

    /**
     * @return MemcacheService
     */
    public function getMemcache()
    {
        return $this->memcache;
    }

    /**
     * @param      $cacheId
     * @param null $default
     * @param bool $cached
     * @return mixed|null
     */
    public function getCacheItem($cacheId, $default = null, $cached = true)
    {
        if ($this->isCacheAllowed($cached) && isset(MemcacheService::$prefetchCacheData[$cacheId]) && MemcacheService::$prefetchCacheData[$cacheId]) {
            $result = MemcacheService::$prefetchCacheData[$cacheId];
        } elseif ($result = $this->getMemcache()->get($cacheId, $default)) {
            return $result;
        }

        if (is_null($result)) {
            $result = $default;
        }

        return $result;
    }

    /**
     * @param bool $cached
     * @return bool
     */
    protected function isCacheAllowed($cached = true)
    {
        return MemcacheService::isCacheAllowed($cached);
    }

    /**
     * @param      $cacheId
     * @param null $default
     * @param bool $cached
     * @return mixed|null
     */
    public function getCacheDecodedItem($cacheId, $default = null, $cached = true)
    {
        $mc = $this->getMemcache();

        if ($this->isCacheAllowed($cached) && isset(MemcacheService::$prefetchCacheData[$cacheId]) && MemcacheService::$prefetchCacheData[$cacheId]) {
            $result = MemcacheService::$prefetchCacheData[$cacheId];
        } elseif (($result = $mc->get($cacheId, $default, $cached)) && ($result != $default)) {
            $result = @json_decode($result, true);
        }

        if (is_null($result)) {
            $result = $default;
        }

        return $result;
    }

    /**
     * @param        $cacheId
     * @param        $data
     * @param string $ttl
     * @return mixed
     */
    public function setCacheItem($cacheId, $data, $ttl = 'cache_ttl_middle')
    {
        if ($ttl == 'cache_ttl_middle') {
            $ttl = $this->getCacheTtlMiddle();
        }
        $this->getMemcache()->set($cacheId, $data, $ttl);

        if (MemcacheService::$addToPrefetchOnSet) {
            MemcacheService::$prefetchCacheData[$cacheId] = $data;
        }

        return $data;
    }

    /**
     * Time to live from 1 to 3 days
     *
     * @return int
     */
    public function getCacheTtlMiddle()
    {
        return mt_rand(86400, 3 * 86400);
    }

    /**
     * @param $cacheId
     * @return bool
     */
    public function deleteCacheItem($cacheId)
    {
        return $this->getMemcache()->delete($cacheId);
    }

    /**
     * @param array $keys
     * @return array|bool
     */
    public function deleteCacheMultiple(array $keys)
    {
        return $this->getMemcache()->deleteMultiple($keys);
    }

    /**
     * @param        $cacheId
     * @param        $data
     * @param string $ttl
     * @return mixed
     */
    public function setCacheEncodedItem($cacheId, $data, $ttl = 'cache_ttl_middle')
    {
        if ($ttl == 'cache_ttl_middle') {
            $ttl = $this->getCacheTtlMiddle();
        }
        $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->getMemcache()->set($cacheId, $encodedData, $ttl);

        if (MemcacheService::$addToPrefetchOnSet) {
            MemcacheService::$prefetchCacheData[$cacheId] = $data;
        }

        return $data;
    }

    /**
     * Time to live from 1 to 3 hours
     *
     * @return int
     */
    public function getCacheTtlShort()
    {
        return mt_rand(3600, 10800);
    }

    /**
     * Time to live from 7 to 21 days
     *
     * @return int
     */
    public function getCacheTtlLong()
    {
        return mt_rand(7 * 86400, 21 * 86400);
    }
}
