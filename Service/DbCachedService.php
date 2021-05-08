<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;

final class DbCachedService
{
    use CacheTrait;

    private $db;
    private $ttl;
    private $cacheId;
    private $cached;

    public function __construct(MemcacheService $memcache, DbService $db, $cached = true, $ttl = null, $cacheId = null)
    {
        $this->setMemcache($memcache);
        $this->db = $db;
        $this->cached = (bool)$cached;
        $this->cacheId = $cacheId;
        if (is_null($ttl) || !$ttl) {
            $this->ttl = $this->getCacheTtlShort();
        } else {
            $this->ttl = $ttl;
        }
    }

    private function buildCacheId($sql, $params, $salt, $object = null)
    {
        if (!$this->cacheId) {
            $parts = $object ? explode('\\', $object) : [];
            if ($parts) {
                $prefix = end($parts);
            } else {
                $prefix = $salt;
            }

            $paramsHash = '';
            if (is_array($params)) {
                $paramsHash = serialize($params);
            }

            return $prefix.'_'.md5($sql.'_'.$salt.'_'.$paramsHash);
        }

        return $this->cacheId;
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $query  The SQL query to be executed.
     * @param array  $params The prepared statement params.
     * @param array  $types  The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchOne(string $query, array $params = [], array $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($query, $params, 'fetchOne');
        if ($result = $this->getCacheItem($cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchOne($query, $params, $types, $isSlave)) {
            $this->setCacheItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $query  The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @param bool   $isSlave
     * @return array|bool False is returned if no rows are found.
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [], bool $isSlave = false)
    {
        $cacheId = $this->buildCacheId($query, $params, 'fetchAssoc');
        if ($result = $this->getCacheDecodedItem($cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchAssociative($query, $params, $types, $isSlave)) {
            $this->setCacheEncodedItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @param bool                                                                 $isSlave
     * @return mixed
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = [], bool $isSlave = false)
    {
        $cacheId = $this->buildCacheId($query, $params, 'fetchAllAssociative');
        if ($result = $this->getCacheDecodedItem($cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchAllAssociative($query, $params, $types, $isSlave)) {
            $this->setCacheEncodedItem($cacheId, $result, $this->ttl);
        }

        return $result;
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @param bool   $isSlave
     * @return array|Statement|false The executed statement.
     */
    public function fetchPairs($query, array $params = [], $types = [], $isSlave = false)
    {
        $cacheId = $this->buildCacheId($query, $params, 'fetchPairs');
        if ($result = $this->getCacheDecodedItem($cacheId, null, $this->cached)) {
            return $result;
        }

        if ($result = $this->db->fetchPairs($query, $params, $types, $isSlave)) {
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
}
