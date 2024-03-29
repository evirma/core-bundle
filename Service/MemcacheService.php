<?php

namespace Evirma\Bundle\CoreBundle\Service;

use ErrorException;
use Exception;
use Memcached;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class MemcacheService
{
    const MC_DEFAULT = '-1~1982~06~01';

    public static $prefetchCacheData = [];
    public static $addToPrefetchOnSet = false;
    public static $profillerEnable = false;

    /**
     * @var bool
     */
    public static $isCacheAllowed = true;
    /**
     * Transaction started flag
     *
     * @var bool
     */
    private $transaction = false;
    /**
     * @var array
     */
    private $transactionCachedIds = [];
    /**
     * @var Memcached
     */
    private $memcached;
    /**
     * @var string
     */
    private $prefix;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    public static $profiller;

    /**
     * @param bool $state
     */
    public static function setCacheAllowed(bool $state)
    {
        self::$isCacheAllowed = $state;
    }

    /**
     * @required
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function beginTransaction()
    {
        $this->transaction = true;

        return $this;
    }

    public function commit()
    {
        $this->transaction = false;
        $this->transactionCachedIds = [];

        return $this;
    }

    public function rollBack()
    {
        if ($this->transaction) {
            $this->transaction = false;
            if ($this->transactionCachedIds) {
                $this->deleteMultiple($this->transactionCachedIds);
            }
            $this->transactionCachedIds = [];
        }

        return $this;
    }

    /**
     * @param array $keys
     * @return bool|array
     */
    public function deleteMultiple(array $keys)
    {
        try {
            return $this->getMemcachedAdapter()->deleteMulti($keys);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached deleteMultiple failed", ['keys' => $keys, 'e' => $e]);

            return false;
        }
    }

    /**
     * @param bool $renewStoreKey
     * @return Memcached
     * @throws ErrorException
     */
    private function getMemcachedAdapter($renewStoreKey = false)
    {
        if ($this->memcached && !$renewStoreKey) {
            return $this->memcached;
        }

        $options = [];
        $this->memcached = MemcachedAdapter::createConnection(
            'memcached://memcached-server',
            $options
        );

        if ($this->prefix) {
            $prefixStoreKey = $this->getPrefixStoreKey();
            if (!($prefix = $this->get($prefixStoreKey)) || $renewStoreKey) {
                $prefix = $this->prefix.mt_rand(1, 100000).'_';
                $this->set($prefixStoreKey, $prefix, 365 * 86400);
            }
            $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
        }

        return $this->memcached;
    }

    protected function getPrefixStoreKey()
    {
        return $this->prefix.'prefix_store';
    }

    /**
     * @param      $key
     * @param      $default
     * @param bool $cached
     * @return mixed|null
     */
    public function get($key, $default = false, $cached = true)
    {
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        try {
            $result = $this->getMemcachedAdapter()->get($key);
            if ($this->getMemcachedAdapter()->getResultCode() == Memcached::RES_NOTFOUND) {
                return $default;
            }

            return $result;
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached get failed", ['key' => $key, 'e' => $e]);

            return $default;
        }
    }

    /**
     * @param bool $cached
     * @return bool
     */
    public static function isCacheAllowed($cached = true)
    {
        return self::$isCacheAllowed && $cached;
    }

    /**
     * @param       $key
     * @param null  $value
     * @param int   $ttl
     * @return bool
     */
    public function set($key, $value = null, $ttl = null)
    {
        if ($this->transaction) {
            $this->transactionCachedIds[$key] = $key;
        }

        try {
            return $this->getMemcachedAdapter()->set($key, $value, time() + $ttl);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached set failed", ['key' => $key, 'value' => $value, 'e' => $e]);

            return false;
        }
    }

    /**
     * @param      $values
     * @param null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        try {
            return $this->getMemcachedAdapter()->setMulti($values, time() + $ttl);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached setMultiple failed", ['values' => $values, 'e' => $e]);

            return false;
        }
    }

    /**
     * @param      $keys
     * @param      $default
     * @param bool $cached
     * @return bool|iterable|null
     */
    public function getMultiple($keys, $default = false, $cached = true)
    {
        if (!$this->isCacheAllowed($cached) || !is_array($keys)) {
            return $default;
        }

        $keys = array_values($keys);

        try {
            return $this->getMemcachedAdapter()->getMulti($keys);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached getMultiple failed", ['keys' => $keys, 'default' => $default, 'e' => $e]);

            return $default;
        }
    }

    /**
     * @param      $key
     * @param      $default
     * @param bool $cached
     * @return mixed|null
     */
    public function getDecoded($key, $default = false, $cached = true)
    {
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        $result = $this->get($key, self::MC_DEFAULT);

        if ($result != self::MC_DEFAULT && $result) {
            $result = @json_decode($result, true);
        } else {
            $result = $default;
        }

        return $result;
    }

    /**
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        try {
            return $this->getMemcachedAdapter()->delete($key);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached deleteMultiple failed", ['key' => $key, 'e' => $e]);

            return false;
        }
    }

    /**
     * @return bool
     */
    public function deletePrefix()
    {
        try {
            $this->getMemcachedAdapter(true);

            return true;
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Delete Prefix Key Failed", ['e' => $e]);

            return false;
        }
    }
}