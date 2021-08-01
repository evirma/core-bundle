<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;

final class DbService
{
    use CacheTrait;

    private LoggerInterface $logger;
    private DbDriverService $driver;

    private bool $throwException = false;

    /**
     * DbService constructor.
     *
     * @param LoggerInterface $logger
     * @param DbDriverService $driver
     */
    public function __construct(LoggerInterface $logger, DbDriverService $driver)
    {
        $this->logger = $logger;
        $this->driver = $driver;
    }

    /**
     * @param false $isSlave
     * @return DbDriverService|object
     */
    private function db($isSlave = false)
    {
        if (!$this->throwException) {
            return $this->driver->setDefaultConnectionName($isSlave ? 'slave' : null);
        } else {
            return $this->getDoctrineManager()->getConnection($isSlave ? 'slave' : null);
        }
    }

    /**
     * @return ManagerRegistry
     */
    #[Pure] public function getDoctrineManager(): ManagerRegistry
    {
        return $this->driver->getDoctrineManager();
    }

    /**
     * @return EntityManager|object
     */
    public function getEm()
    {
        return $this->getDoctrineManager()->getManager();
    }

    /**
     * @param string|null $name
     * @return Connection|object
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getConnection(?string $name = null)
    {
        return $this->driver->getDoctrineManager()->getConnection($name);
    }

    /**
     * @return Connection|object
     */
    public function getConn()
    {
        return $this->getConnection();
    }

    /**
     * @return Connection|object
     */
    public function getConnSlave()
    {
        return $this->getConnection('slave');
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
     */
    public function commit()
    {
        $this->db()->commit();
    }

    /**
     * Cancels any database changes done during the current transaction.
     */
    public function rollBack()
    {
        $this->db()->rollBack();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     * @param bool $isSlave
     *
     * @return mixed
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = [], bool $isSlave = false)
    {
        return $this->db($isSlave)->fetchAllAssociative($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $object The Object Class
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @param bool   $isSlave
     * @return array|false
     */
    public function fetchObjectAll($object, $sql, array $params = [], $types = [], $isSlave = false)
    {
        if ($data = $this->db($isSlave)->fetchAllAssociative($sql, $params, $types)) {
            foreach ($data as &$item) {
                $item = $this->createObject($object, $item);
            }
        }

        return $data;
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

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $query The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return array|bool False is returned if no rows are found.
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [], bool $isSlave = false)
    {
        return $this->db($isSlave)->fetchAssociative($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $object The Object Class
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchObject($object, $statement, array $params = [], array $types = [], $isSlave = false)
    {
        if ($item = $this->db($isSlave)->fetchAssociative($statement, $params, $types)) {
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
     * @param string $query The SQL query to be executed.
     * @param array  $params    The prepared statement params.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchOne(string $query, array $params = [], array $types = [], $isSlave = false)
    {
        return $this->db($isSlave)->fetchOne($query, $params, $types);
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
        return $this->db($isSlave)->fetchPairs($query, $params, $types);
    }

    /**
     * Альтернативный метод выбора уникальныйх ID, уникальность соблюдается за счет ключа массива
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @param bool   $isSlave
     * @return array|Statement|false The executed statement.
     */
    public function fetchUniqIds($query, array $params = [], $types = [], $isSlave = false)
    {
        return $this->db($isSlave)->fetchUniqIds($query, $params, $types);
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query   The SQL query to execute.
     * @param array  $params  The parameters to bind to the query, if any.
     * @param array  $types   The types the previous parameters are in.
     * @param bool   $isSlave The query cache profile, optional.
     * @return Statement|false The executed statement.
     */
    public function executeQuery($query, array $params = [], $types = [], $isSlave = false)
    {
        return $this->db($isSlave)->executeQuery($query, $params, $types);
    }

    /**
     * Inserts a table row with specified data.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array  $data            An associative array containing column-value pairs.
     * @param array  $types           Types of the inserted data.
     * @return int The number of affected rows.
     */
    public function insert($tableExpression, array $data, array $types = [])
    {
        return $this->db()->insert($tableExpression, $data, $types);
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
     */
    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        return $this->db()->update($tableExpression, $data, $identifier, $types);
    }

    /**
     * @param       $tableExpression
     * @param array $data
     * @return bool|Statement|false
     */
    public function upsert($tableExpression, array $data)
    {
        return $this->db()->upsert($tableExpression, $data);
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
        return $this->db()->prepareMultipleValues($data, $includeFields, $excludeFields, $cast);
    }

    public function checkConnection($isSlave = false)
    {
        return $this->db($isSlave)->checkConnection();
    }

    public function reconnect($isSlave = false, $tries = 5)
    {
        return $this->db($isSlave)->reconnect($tries);
    }

    /**
     * @return bool
     */
    public function isThrowException(): bool
    {
        return $this->throwException;
    }

    /**
     * @param mixed $throwException
     * @return bool
     */
    public function setThrowException($throwException): bool
    {
        $oldStatus = $this->throwException;
        $this->throwException = (bool)$throwException;

        return $oldStatus;
    }
}
