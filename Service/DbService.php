<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ForwardCompatibility\DriverResultStatement;
use Doctrine\DBAL\ForwardCompatibility\DriverStatement;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Domain\Exception\Repository\SqlDriverException;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use PDO;
use Psr\Log\LoggerInterface;
use Throwable;
use Traversable;

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
     * @param ManagerRegistry  $manager
     * @param ?LoggerInterface $logger
     * @param string|null      $connectionName
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

        $cacheName = $connectionName ?: 'default';
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
     * @param string|null $name
     * @return EntityManager|object
     */
    public function getEm(string $name = null)
    {
        return $this->manager->getManager($name);
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
     * @return Connection|object
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getConnection(?string $name = null)
    {
        return $this->manager->getConnection($name);
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     * @throws SqlDriverException
     */
    public function commit()
    {
        try {
            $this->db()->commit();
        } catch (ConnectionException $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @param Throwable $e
     * @param null      $sql
     * @param array     $params
     * @param array     $types
     * @return  SqlDriverException
     */
    private function convertException(Throwable $e, $sql = null, array $params = [], array $types = []): SqlDriverException
    {
        $message = $e->getMessage();
        $message = preg_replace('#VALUES(.*?)ON\s+CONFLICT#usi', 'VALUES ({{VALUES}}) ON CONFLICT', $message);
        $message = preg_replace('#with params\s*\[.*?]#usi', 'with params [{{PARAMS}}]', $message);

        $exception = new SqlDriverException($message, $e);
        $this->logger?->error('SQL Execute Error', [
            'message' => $message,
            'connection' => $this->connectionName ?: 'default',
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
            'exception' => $exception,
        ]);

        return $exception;
    }

    /**
     * Cancels any database changes done during the current transaction.
     *
     * @throws SqlDriverException
     */
    public function rollBack()
    {
        try {
            $this->db()->rollBack();
        } catch (ConnectionException $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array|bool False is returned if no rows are found.
     * @throws SqlDriverException
     */
    public function fetchAssociative(string $sql, array $params = [], array $types = [])
    {
        $sql = $this->executeQuery($sql, $params, $types);
        try {
            return $sql ? $sql->fetchAssociative() : false;
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $sql    SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return DriverStatement|DriverResultStatement The executed statement or the cached result statement if a query cache profile is used
     * @throws SqlDriverException
     */
    public function executeQuery($sql, array $params = [], $types = [])
    {
        try {
            return $this->db()->executeQuery($sql, $params, $types);
        } catch (DBALException $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $object The Object Class
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @return array
     * @throws SqlDriverException
     */
    public function fetchObjectAll($object, $sql, array $params = [], $types = []): array
    {
        if ($data = $this->fetchAllAssociative($sql, $params, $types)) {
            foreach ($data as &$item) {
                $item = $this->createObject($object, $item);
            }
        }

        return $data;
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string $sql    SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllAssociative(string $sql, array $params = [], array $types = [])
    {
        $sql = $this->executeQuery($sql, $params, $types);
        try {
            return $sql->fetchAllAssociative();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
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
     * @param string $sql    The SQL query to be executed.
     * @param array  $params The prepared statement params.
     * @param array  $types  The query parameter types.
     * @return mixed|bool False is returned if no rows are found.
     * @throws SqlDriverException
     */
    public function fetchOne($sql, array $params = [], array $types = [])
    {
        if (!$sql = $this->executeQuery($sql, $params, $types)) {
            return false;
        }

        try {
            return $sql->fetchOne();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @param string $sql    SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return array<int,mixed>
     * @throws SqlDriverException
     */
    public function fetchFirstColumn($sql, array $params = [], array $types = [])
    {
        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchFirstColumn();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                           $sql    SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllKeyValue($sql, array $params = [], $types = []): array
    {
        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchAllKeyValue();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param       $sql
     * @param array $params Query parameters
     * @param array $types  Parameter types
     * @return array
     * @throws SqlDriverException
     */
    public function fetchAllAssociativeIndexed($sql, array $params = [], $types = []): array
    {
        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            return $stmt->fetchAllAssociativeIndexed();
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * @param string $query  SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): Traversable
    {
        $stmt = $this->executeQuery($query, $params, $types);

        try {
            return $stmt->iterateKeyValue();
        } catch (Exception $e) {
            throw $this->convertException($e, $query, $params, $types);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string $query  SQL query
     * @param array  $params Query parameters
     * @param array  $types  Parameter types
     * @return Traversable
     * @throws SqlDriverException
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): Traversable
    {
        $stmt = $this->executeQuery($query, $params, $types);

        try {
            return $stmt->iterateAssociativeIndexed();
        } catch (Exception $e) {
            throw $this->convertException($e, $query, $params, $types);
        }
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $sql    The SQL query to execute.
     * @param array  $params The parameters to bind to the query, if any.
     * @param array  $types  The types the previous parameters are in.
     * @return array The executed statement.
     * @throws SqlDriverException
     * @deprecated use self::fetchAllKeyValue
     */
    public function fetchPairs($sql, array $params = [], $types = [])
    {
        $sql = $this->executeQuery($sql, $params, $types);
        try {
            if ($sql && ($data = $sql->fetchAllNumeric())) {
                $result = [];
                foreach ($data as $item) {
                    $result[$item[0]] = $item[1];
                }

                return $result;
            }
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }

        return null;
    }

    /**
     * Альтернативный метод выбора уникальныйх ID, уникальность соблюдается за счет ключа массива
     *
     * @param       $sql
     * @param array $params The parameters to bind to the query, if any.
     * @param array $types  The types the previous parameters are in.
     * @return array|null The executed statement.
     * @throws SqlDriverException
     */
    public function fetchUniqIds($sql, array $params = [], $types = [])
    {
        $stmt = $this->executeQuery($sql, $params, $types);

        try {
            if ($stmt && ($data = $stmt->fetchAllNumeric())) {
                $result = [];
                foreach ($data as $item) {
                    $result[$item[0]] = $item[0];
                }

                return $result;
            }
        } catch (Exception $e) {
            throw $this->convertException($e, $sql, $params, $types);
        }

        return null;
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     * Could be used for:
     *  - DML statements: INSERT, UPDATE, DELETE, etc.
     *  - DDL statements: CREATE, DROP, ALTER, etc.
     *  - DCL statements: GRANT, REVOKE, etc.
     *  - Session control statements: ALTER SESSION, SET, DECLARE, etc.
     *  - Other statements that don't yield a row set.
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $sql    SQL statement
     * @param array  $params Statement parameters
     * @param array  $types  Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
        try {
            return $this->db()->executeStatement($sql, $params, $types);
        } catch (DBALException $e) {
            throw $this->convertException($e, $sql, $params, $types);
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
     * @throws SqlDriverException
     */
    public function insert($tableExpression, array $data, array $types = [])
    {
        try {
            return $this->db()->insert($tableExpression, $data, $types);
        } catch (DBALException $e) {
            throw $this->convertException($e);
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
     * @throws SqlDriverException
     */
    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        try {
            return $this->db()->update($tableExpression, $data, $identifier, $types);
        } catch (DBALException $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Executes an SQL DELETE statement on a table.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $table    Table name
     * @param array  $criteria Deletion criteria
     * @param array  $types    Parameter types
     * @return int The number of affected rows.
     * @throws SqlDriverException
     */
    public function delete($table, array $criteria, array $types = [])
    {
        try {
            return $this->db()->delete($table, $criteria, $types);
        } catch (DBALException $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * @param        $table
     * @param array  $data
     * @param array  $cast
     * @param array  $conflict
     * @param array  $do
     * @param string $doWhere
     * @return bool|Statement|false
     * @throws SqlDriverException
     */
    public function upsert($table, array $data, array $cast = [], array $conflict = [], array $do = [], string $doWhere = '')
    {
        if (empty($data)) {
            return false;
        }

        $includeFields = array_keys($data[0]);
        $includeFieldsStr = implode(', ', $includeFields);

        [$values, $params] = $this->prepareMultipleValues($data, $includeFields, [], $cast);

        if ($values) {
            $conflictStr = empty($conflict) ? '' : ' (' . implode(',', $conflict) . ')';
            $doStr = empty($do) ? 'NOTHING' : $this->prepareDo($do, $doWhere);

            /** @noinspection SqlNoDataSourceInspection */
            $sql = "INSERT INTO $table ($includeFieldsStr) VALUES $values ON CONFLICT$conflictStr DO $doStr";

            return $this->executeQuery($sql, $params);
        }

        return true;
    }

    /**
     * Build Do construction like UPDATE field = EXCLUDED.fields
     *
     * @param array  $do
     * @param string $doWhere
     * @return string
     */
    protected function prepareDo(array $do = [], string $doWhere = ''): string
    {
        if (empty($do)) {
            return 'NOTHING';
        }

        $doStr = 'DO UPDATE SET ';
        foreach ($do as $key => $field) {
            if (is_int($key)) {
                $doStr .= "$field = EXCLUDED.$field, ";
            } else {
                $doStr .= "$key = $field, ";
            }
        }

        $doStr = rtrim($doStr, ', ');

        if ($doWhere) {
            $doStr .= ' WHERE ' . $doWhere;
        }

        return $doStr;
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

    public function reconnect($tries = 5)
    {
        if (!$isConnected = $this->checkConnection()) {
            $this->db()->connect();

            $isConnected = $this->checkConnection();
            if (--$tries <= 0) {
                return $isConnected;
            }

            if (!$isConnected) {
                sleep((6 - $tries) * 2);

                return $this->reconnect($tries);
            }
        }

        return $isConnected;
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

    /**
     * @return Connection|object
     * @deprecated
     */
    public function getConn()
    {
        return $this->getConnection();
    }

    /**
     * @return Connection|object
     * @deprecated
     */
    public function getConnSlave()
    {
        return $this->getConnection('slave');
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
