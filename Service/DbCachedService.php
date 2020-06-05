<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Evirma\Bundle\CoreBundle\Traits\CacheTrait;

final class DbCachedService
{
    use CacheTrait;

    private $db;
    private $ttl;
    private $cacheId;
    private $cached;

    public function __construct(MemcacheService $memcache, DbService $db, $cached = true, $cacheId = null, $ttl = null)
    {
        $this->setMemcache($memcache);
        $this->db = $db;
        $this->cached = (bool)$cached;
        $this->cacheId = $cacheId;
        if (is_null($ttl)) {
            $this->ttl = $this->getCacheTtlShort();
        }
    }

    /**
     * @param string $object    The Object Class
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchObject($object, $statement, array $params = [], array $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($object, $statement, $params, 'fetchObject');
        if ($result = $this->getObjectCacheDecodedItem($object, $cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchObject($object, $statement, $params, $types, $isSlave)) {
            $this->setCacheEncodedItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    /**
     * @param string $object    The Object Class
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchObjectAll($object, $statement, array $params = [], array $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($object, $statement, $params, 'fetchObjectAll');
        if ($result = $this->getObjectCacheDecodedList($object, $cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchObjectAll($object, $statement, $params, $types, $isSlave)) {
            $this->setCacheEncodedItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    private function buildCacheId($object, $sql, $params, $salt)
    {
        if (!$this->cacheId) {
            $parts = explode('\\', $object);
            $prefix = end($parts);

            return $prefix.'_'.md5($sql.'_'.(string)$salt.'_'.serialize($params));
        }

        return $this->cacheId;
    }
}
