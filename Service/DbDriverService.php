<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;

final class DbDriverService
{
    /**
     * @var ManagerRegistry
     */
    private $manager;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $conn;

    /**
     * DbService constructor.
     *
     * @param ManagerRegistry $manager
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        if ($this->logger) {
            return $this->logger;
        }

        return $this->logger;
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrineManager(): ManagerRegistry
    {
        return $this->manager;
    }

    /**
     * @return EntityManager|object
     */
    public function getEm()
    {
        if (!$this->em) {
            $this->em = $this->manager->getManager();
        }

        return $this->em;
    }

    /**
     * @param $name
     * @return Connection|object
     */
    public function getConnection($name)
    {
        return $this->manager->getConnection($name);
    }


    /**
     * @return Connection|object
     */
    public function getConn()
    {
        if (!$this->conn) {
            $this->conn = $this->manager->getConnection();
        }
        return $this->conn;
    }

    /**
     * @return Connection|object
     */
    public function getConnSlave()
    {
        try {
            return $this->manager->getConnection('slave');
        } catch (InvalidArgumentException $e) {
            return $this->getConn();
        }
    }

    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->getConn()->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     */
    public function commit()
    {
        try {
            $this->getConn()->commit();
        } catch (ConnectionException $e) {
            $this->getLogger()->error('SQL Commit Failed', ['message' => $e->getMessage(), 'exception' => $e]);
        }
    }

    /**
     * Cancels any database changes done during the current transaction.
     */
    public function rollBack()
    {
        try {
            $this->getConn()->rollBack();
        } catch (ConnectionException $e) {
            $this->getLogger()->error('SQL RollBack Failed', ['message' => $e->getMessage(), 'exception' => $e]);
        }
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
        $query = $this->executeQuery($query, $params, $types, $isSlave);
        return $query ? $query->fetchAssociative() : false;
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
        $query = $this->executeQuery($query, $params, $types, $isSlave);
        return $query ? $query->fetchAllAssociative() : false;
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $statement The SQL query to be executed.
     * @param array  $params    The prepared statement params.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchOne($statement, array $params = [], array $types = [], $isSlave = false)
    {
        $query = $this->executeQuery($statement, $params, $types, $isSlave);
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
     * @param bool   $isSlave
     * @return array|Statement|false The executed statement.
     */
    public function fetchPairs($query, array $params = [], $types = [], $isSlave = false)
    {
        $query = $this->executeQuery($query, $params, $types, $isSlave);
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
     * @param bool   $isSlave
     * @return array|Statement|false The executed statement.
     */
    public function fetchUniqIds($query, array $params = [], $types = [], $isSlave = false)
    {
        $query = $this->executeQuery($query, $params, $types, $isSlave);
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
     * @param string $query   The SQL query to execute.
     * @param array  $params  The parameters to bind to the query, if any.
     * @param array  $types   The types the previous parameters are in.
     * @param bool   $isSlave The query cache profile, optional.
     * @return Statement|false The executed statement.
     */
    public function executeQuery($query, array $params = [], $types = [], $isSlave = false)
    {
        $result = false;
        try {
            $conn = $isSlave ? $this->getConnSlave() : $this->getConn();
            $result = $conn->executeQuery($query, $params, $types);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message = preg_replace('#VALUES(.*?)ON\s+CONFLICT#usi', 'VALUES ({{VALUES}}) ON CONFLICT', $message);
            $message = preg_replace('#with params\s*\[.*?]#usi', 'with params [{{PARAMS}}]', $message);

            $this->getLogger()->error('SQL Execute Error', ['message' => $message, 'sql' => $query, 'params' => $params, 'types' => $types, 'exception' => $e]);
        }

        return $result;
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
        $result = false;
        try {
            $result = $this->getConn()->insert($tableExpression, $data, $types);
        } catch (Exception $e) {
            $this->getLogger()->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
        }

        return $result;
    }

    /**
     * @param null $seqName
     * @return string
     */
    public function lastInsertId($seqName = null)
    {
        return $this->getConn()->lastInsertId($seqName);
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
        $result = false;
        try {
            $result = $this->getConn()->update($tableExpression, $data, $identifier, $types);
        } catch (Exception $e) {
            $this->getLogger()->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'identifier' => $identifier, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
        }

        return $result;
    }

    /**
     * @param       $tableExpression
     * @param array $data
     * @return bool|Statement|false
     */
    public function upsert($tableExpression, array $data)
    {
        $includeFields = array_keys($data[0]);
        $includeFieldsStr = implode(', ', $includeFields);

        [$values, $params] = $this->prepareMultipleValues($data, $includeFields);

        if ($values) {
            /** @noinspection SqlNoDataSourceInspection */
            $sql = "INSERT INTO {$tableExpression} ({$includeFieldsStr}) VALUES {$values} ON CONFLICT DO NOTHING";
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
        $conn = $this->getConn();
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
                    $castType = isset($cast[$key]) ? $cast[$key] : '';
                    if (is_bool($value)) {
                        $value = $value ? 'TRUE' : 'FALSE';
                    }
                    $castTypeStr = $i ? '' : $castType;
                    if ($castType) {
                        $sqlValue .= ",{$castTypeStr} ".$conn->quote($value, PDO::PARAM_STR);
                    } else {
                        $sqlValue .= ", :{$key}__{$i}";
                        $params["{$key}__{$i}"] = $value;
                    }
                }
            }

            $sqlValue = ltrim($sqlValue, ', ');
            $sql .= ",\n({$sqlValue})";
            $i++;
        }

        $sql = ltrim($sql, ', ');

        return [$sql, $params];
    }

    public function checkConnection($isSlave = false)
    {
        $conn = $isSlave ? $this->getConnSlave() : $this->getConn();

        try {
            $conn->executeQuery("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function reconnect($isSlave = false, $tries = 5)
    {
        if (!$isConnected = $this->checkConnection($isSlave)) {
            $conn = $isSlave ? $this->getConnSlave() : $this->getConn();
            $conn->connect();

            $isConnected = $this->checkConnection($isSlave);
            if (--$tries <= 0) {
                return $isConnected;
            }

            if (!$isConnected) {
                sleep((6-$tries)*2);
                return $this->reconnect($isSlave, $tries);
            }
        }

        return $isConnected;
    }
}
