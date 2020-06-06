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
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $statement The SQL query to be executed.
     * @param array  $params    The prepared statement params.
     * @param int    $column    The 0-indexed column number to retrieve.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchColumn($statement, array $params = [], $column = 0, array $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($statement, $params, 'fetchColumn');
        if ($result = $this->getCacheItem($cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchColumn($statement, $params, $column, $types, $isSlave)) {
            $this->setCacheItem($cacheId, $result, $this->ttl);
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
    public function fetchObject($object, $statement, array $params = [], array $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($statement, $params, 'fetchObject', $object);
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
        $cacheId = $this->buildCacheId($statement, $params, 'fetchObjectAll', $object);
        if ($result = $this->getObjectCacheDecodedList($object, $cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchObjectAll($object, $statement, $params, $types, $isSlave)) {
            $this->setCacheEncodedItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    private function buildCacheId($sql, $params, $salt, $object = null)
    {
        if (!$this->cacheId) {
            $parts = $object ? explode('\\', $object) : $salt;
            $prefix = end($parts);

            return $prefix.'_'.md5($sql.'_'.(string)$salt.'_'.serialize($params));
        }

        return $this->cacheId;
    }
}
