<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use PDO;
use Psr\Log\LoggerInterface;

final class DbService
{
    use CacheTrait;

    private ?string $connectionName;
    private ManagerRegistry $manager;
    private ?LoggerInterface $logger;
    private $db = null;

    /** @var array<DbService> */
    private $servers = [];

    /**
     * DbService constructor.
     *
     * @param ManagerRegistry $manager
     * @param ?LoggerInterface $logger
     * @param string|null     $connectionName
     */
    public function __construct(ManagerRegistry $manager, ?LoggerInterface $logger = null, ?string $connectionName = null)
    {
        $this->logger = $logger;
        $this->connectionName = $connectionName;
        $this->manager = $manager;
    }

    /**
     * @param string|null $connectionName
     * @return $this
     */
    public function server(string $connectionName = null): Dbservice
    {
        if ($this->connectionName == $connectionName) {
            return $this;
        }

        $cacheName = $connectionName?: 'default';
        if (!isset($this->servers[$cacheName])) {
            $this->servers[$cacheName] = new DbService($this->manager, $this->logger, $connectionName);
        }
        return $this->servers[$cacheName];
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrineManager(): ManagerRegistry
    {
        return $this->manager;
    }

    /**
     * @return Connection|object
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    private function db()
    {
        if (!$this->db) {
            $this->db = $this->getConnection($this->connectionName);
        }

        return $this->db;
    }

    /**
     * @param string|null $name
     * @return EntityManager|object
     */
    public function getEm(string $name = null)
    {
        return $this->manager->getManager($name);
    }

    /**
     * @param string|null $name
     * @return Connection|object
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getConnection(?string $name = null)
    {
        return $this->manager->getConnection($name);
    }


    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->db()->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     * @throws ConnectionException
     */
    public function commit()
    {
        try {
            $this->db()->commit();
        } catch (ConnectionException $e) {
            $this->logger?->error('SQL Commit Failed', $this->eParams($e));
            throw $e;
        }
    }

    /**
     * Cancels any database changes done during the current transaction.
     *
     * @throws ConnectionException
     */
    public function rollBack()
    {
        try {
            $this->db()->rollBack();
        } catch (ConnectionException $e) {
            $this->logger?->error('SQL RollBack Failed', $this->eParams($e));
            throw $e;
        }
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $query  The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array|bool False is returned if no rows are found.
     * @throws DBALException
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        return $query ? $query->fetchAssociative() : false;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @return mixed
     * @throws DBALException
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        return $query ? $query->fetchAllAssociative() : false;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $object The Object Class
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array|false
     * @throws DBALException
     */
    public function fetchObjectAll($object, $sql, array $params = [], $types = [])
    {
        if ($data = $this->fetchAllAssociative($sql, $params, $types)) {
            foreach ($data as &$item) {
                $item = $this->createObject($object, $item);
            }
        }

        return $data;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $object    The Object Class
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @return mixed|bool False is returned if no rows are found.
     * @throws DBALException
     */
    public function fetchObject($object, $statement, array $params = [], array $types = [])
    {
        if ($item = $this->db()->fetchAssociative($statement, $params, $types)) {
            $item = $this->createObject($object, $item);
        }

        return $item;
    }

    /**
     * Использовать кеширование для запросов
     *
     * @param bool $cached
     * @param null $cacheId
     * @param null $ttl
     * @return DbCachedService
     */
    public function useCache($cached = true, $ttl = null, $cacheId = null)
    {
        return new DbCachedService($this->getMemcache(), $this, $cached, $ttl, $cacheId);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $query  The SQL query to be executed.
     * @param array  $params The prepared statement params.
     * @param array  $types  The query parameter types.
     * @return mixed|bool False is returned if no rows are found.
     * @throws DBALException
     */
    public function fetchOne(string $query, array $params = [], array $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        return $query ? $query->fetchOne() : false;
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @return array The executed statement.
     * @throws DBALException
     */
    public function fetchPairs($query, array $params = [], $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        if ($query && ($data = $query->fetchAllNumeric())) {
            $result = [];
            foreach ($data as $item) {
                $result[$item[0]] = $item[1];
            }
            return $result;
        }

        return null;
    }

    /**
     * Альтернативный метод выбора уникальныйх ID, уникальность соблюдается за счет ключа массива
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @return array|null The executed statement.
     * @throws DBALException
     */
    public function fetchUniqIds($query, array $params = [], $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        if ($query && ($data = $query->fetchAllNumeric())) {
            $result = [];
            foreach ($data as $item) {
                $result[$item[0]] = $item[0];
            }
            return $result;
        }

        return null;
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @return Statement|false The executed statement.
     * @throws DBALException
     */
    public function executeQuery($query, array $params = [], $types = [])
    {
        try {
            return $this->db()->executeQuery($query, $params, $types);
        } catch (DBALException $e) {
            $message = $e->getMessage();
            $message = preg_replace('#VALUES(.*?)ON\s+CONFLICT#usi', 'VALUES ({{VALUES}}) ON CONFLICT', $message);
            $message = preg_replace('#with params\s*\[.*?]#usi', 'with params [{{PARAMS}}]', $message);

            $this->logger?->error('SQL Execute Error', ['message' => $message, 'sql' => $query, 'params' => $params, 'types' => $types, 'exception' => $e]);

            throw $e;
        }
    }

    /**
     * Inserts a table row with specified data.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array  $types           Types of the inserted data.
     * @return int The number of affected rows.
     * @throws DBALException
     */
    public function insert($tableExpression, array $data, array $types = [])
    {
        try {
            return $this->db()->insert($tableExpression, $data, $types);
        } catch (DBALException $e) {
            $this->logger?->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param null $seqName
     * @return string
     */
    public function lastInsertId($seqName = null)
    {
        return $this->db()->lastInsertId($seqName);
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to update quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array  $identifier      The update criteria. An associative array containing column-value pairs.
     * @param array  $types           Types of the merged $data and $identifier arrays in that order.
     * @return int The number of affected rows.
     * @throws DBALException
     */
    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        try {
            return $this->db()->update($tableExpression, $data, $identifier, $types);
        } catch (DBALException $e) {
            $this->logger?->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'identifier' => $identifier, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param       $tableExpression
     * @param array $data
     * @return bool|Statement|false
     * @throws DBALException
     * @deprecated
     */
    public function upsert($tableExpression, array $data)
    {
        $includeFields = array_keys($data[0]);
        $includeFieldsStr = implode(', ', $includeFields);

        [$values, $params] = $this->prepareMultipleValues($data, $includeFields);

        if ($values) {
            /** @noinspection SqlNoDataSourceInspection */
            $sql = "INSERT INTO $tableExpression ($includeFieldsStr) VALUES $values ON CONFLICT DO NOTHING";
            return $this->executeQuery($sql, $params);
        }

        return true;
    }

    /**
     * @param       $data
     * @param array $includeFields
     * @param array $excludeFields
     * @param array $cast
     * @return array
     */
    public function prepareMultipleValues($data, $includeFields = [], $excludeFields = [], $cast = [])
    {
        $sql = '';
        $params = [];
        $i = 0;
        $conn = $this->db();
        foreach ($data as $item) {
            $sqlValue = '';
            if (!empty($includeFields)) {
                uksort(
                    $item,
                    function ($a, $b) use ($includeFields) {
                        return array_search($a, $includeFields) <=> array_search($b, $includeFields);
                    }
                );
            }

            foreach ($item as $key => $value) {
                $isFieldIncluded = (!empty($includeFields) && in_array($key, $includeFields)) || empty($includeFields);
                $isFieldExcluded = (!empty($excludeFields) && in_array($key, $excludeFields));

                if ($isFieldIncluded && !$isFieldExcluded) {
                    $castType = $cast[$key] ?? '';
                    if (is_bool($value)) {
                        $value = $value ? 'TRUE' : 'FALSE';
                    }
                    $castTypeStr = $i ? '' : $castType;
                    if ($castType && $castType != 'mixed') {
                        $sqlValue .= ",$castTypeStr ".$conn->quote($value, PDO::PARAM_STR);
                    } else {
                        $sqlValue .= ", :".$key."__".$i;
                        $params[$key."__".$i] = $value;
                    }
                }
            }

            $sqlValue = ltrim($sqlValue, ', ');
            $sql .= ",\n($sqlValue)";
            $i++;
        }

        $sql = ltrim($sql, ', ');

        return [$sql, $params];
    }

    public function checkConnection()
    {
        try {
            $this->db()->executeQuery("SELECT 1");
            return true;
        } catch (DBALException) {
            return false;
        }
    }

    public function reconnect($tries = 5)
    {
        if (!$isConnected = $this->checkConnection()) {
            $this->db()->connect();

            $isConnected = $this->checkConnection();
            if (--$tries <= 0) {
                return $isConnected;
            }

            if (!$isConnected) {
                sleep((6-$tries)*2);
                return $this->reconnect($tries);
            }
        }

        return $isConnected;
    }

    /**
     * @deprecated
     * @return Connection|object
     */
    public function getConn()
    {
        return $this->getConnection();
    }

    /**
     * @deprecated
     * @return Connection|object
     */
    public function getConnSlave()
    {
        return $this->getConnection('slave');
    }

    #[ArrayShape(['maessage' => "string", 'connection' => "null|string", 'exception' => "\Exception"])] #[Pure] private function eParams(Exception $e)
    {
        return [
            'maessage' => $e->getMessage(),
            'connection' => $this->connectionName,
            'exception' => $e
        ];
    }

    /**
     * @param               $object
     * @param iterable|null $data
     * @return mixed
     */
    private function createObject($object, iterable $data = null)
    {
        $result = new $object;
        foreach ($data as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    public function disableLogger()
    {
        $this->logger = null;
        return $this;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return DbService
     */
    public function setLogger(?LoggerInterface $logger): DbService
    {
        $this->logger = $logger;

        return $this;
    }
}
