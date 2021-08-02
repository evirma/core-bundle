<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use PDO;
use Psr\Log\LoggerInterface;

final class DbDriverService
{
    private ManagerRegistry $manager;
    private ?LoggerInterface $logger;
    private ?Connection $conn = null;
    private ?string $defaultConnectionName = NULL;

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

    private function getConn(): Connection
    {
        if (!$this->conn) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->conn = $this->manager->getConnection($this->defaultConnectionName);
        }

        return $this->conn;
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrineManager(): ManagerRegistry
    {
        return $this->manager;
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
            $this->logger?->error('SQL Commit Failed', ['message' => $e->getMessage(), 'exception' => $e]);
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
            $this->logger?->error('SQL RollBack Failed', ['message' => $e->getMessage(), 'exception' => $e]);
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
     *
     * @return mixed
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = [])
    {
        $query = $this->executeQuery($query, $params, $types);
        return $query ? $query->fetchAllAssociative() : false;
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $statement The SQL query to be executed.
     * @param array  $params    The prepared statement params.
     * @param array  $types     The query parameter types.
     * @return mixed|bool False is returned if no rows are found.
     */
    public function fetchOne($statement, array $params = [], array $types = [])
    {
        $query = $this->executeQuery($statement, $params, $types);
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
     * @return array The executed statement.
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
     * @param string $query   The SQL query to execute.
     * @param array  $params  The parameters to bind to the query, if any.
     * @param array  $types   The types the previous parameters are in.
     * @return Statement|false The executed statement.
     */
    public function executeQuery($query, array $params = [], $types = [])
    {
        $result = false;
        try {
            $result = $this->getConn()->executeQuery($query, $params, $types);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message = preg_replace('#VALUES(.*?)ON\s+CONFLICT#usi', 'VALUES ({{VALUES}}) ON CONFLICT', $message);
            $message = preg_replace('#with params\s*\[.*?]#usi', 'with params [{{PARAMS}}]', $message);

            $this->logger?->error('SQL Execute Error', ['message' => $message, 'sql' => $query, 'params' => $params, 'types' => $types, 'exception' => $e]);
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
            $this->logger?->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
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
            $this->logger?->error('SQL Execute Error', ['table' => $tableExpression, 'data' => $data, 'identifier' => $identifier, 'types' => $types, 'e' => $e->getMessage(), 'exception' => $e]);
        }

        return $result;
    }

    public function checkConnection()
    {
        try {
            $this->getConn()->executeQuery("SELECT 1");
            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function reconnect($tries = 5)
    {
        if (!$isConnected = $this->checkConnection()) {
            $this->getConn()->connect();

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
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnectionName;
    }

    /**
     * @param ?string $defaultConnectionName
     * @return DbDriverService
     */
    public function setDefaultConnectionName(?string $defaultConnectionName = null): DbDriverService
    {
        $this->defaultConnectionName = $defaultConnectionName;

        return $this;
    }
}
