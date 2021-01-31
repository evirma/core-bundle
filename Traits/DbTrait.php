<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Service\DbService;

/**
 * @deprecated
 */
trait DbTrait
{
    /**
     * @var DbService
     */
    private $dbTraitDb;

    /**
     * @required
     * @param DbService $dbService
     */
    public function setDbService(DbService $dbService)
    {
        $this->dbTraitDb = $dbService;
    }

    /**
     * @deprecated
     * @return EntityManager|object
     */
    protected function getEm()
    {
        return $this->dbTraitDb->getEm();
    }

    /**
     * @deprecated
     * @return ManagerRegistry
     */
    public function getDoctrineManager()
    {
        return $this->dbTraitDb->getDoctrineManager();
    }


    /**
     * @deprecated
     * @param $name
     * @return Connection|object
     */
    public function getConnection($name)
    {
        return $this->dbTraitDb->getConnection($name);

    }

    /**
     * @deprecated
     * @return Connection|object
     */
    public function getConn()
    {
        return $this->dbTraitDb->getConn();
    }

    /**
     * @deprecated
     * @return Connection|object
     */
    public function getConnSlave()
    {
        return $this->dbTraitDb->getConnSlave();
    }

    /**
     * @deprecated
     * @return void
     */
    public function beginTransaction()
    {
        $this->dbTraitDb->beginTransaction();
    }

    /**
     * @deprecated
     * @return void
     */
    public function commit()
    {
        $this->dbTraitDb->commit();
    }

    /**
     * @deprecated
     * Cancels any database changes done during the current transaction.
     */
    public function rollBack()
    {
        $this->dbTraitDb->rollBack();
    }

    /**
     * @deprecated
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     * @param bool   $isSlave
     * @return array|false
     */
    public function fetchAll($sql, array $params = [], $types = [], $isSlave = false)
    {
        return $this->dbTraitDb->fetchAll($sql, $params, $types, $isSlave);
    }

    /**
     * @deprecated
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     * @param bool   $isSlave
     * @return array|bool False is returned if no rows are found.
     */
    public function fetchAssoc($statement, array $params = [], array $types = [], $isSlave = false)
    {
        return $this->dbTraitDb->fetchAssoc($statement, $params, $types, $isSlave);
    }

    /**
     * @deprecated
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
        return $this->dbTraitDb->fetchColumn($statement, $params, $column, $types, $isSlave);
    }

    /**
     * @deprecated
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
        return $this->dbTraitDb->fetchPairs($query, $params, $types, $isSlave);
    }

    /**
     * @deprecated
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
        return $this->dbTraitDb->fetchPairs($query, $params, $types, $isSlave);
    }

    /**
     * @deprecated
     * Executes an, optionally parametrized, SQL query.
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string                 $query  The SQL query to execute.
     * @param array                  $params The parameters to bind to the query, if any.
     * @param array                  $types  The types the previous parameters are in.
     * @param bool                   $isSlave    The query cache profile, optional.
     * @return Statement|false The executed statement.
     */
    public function executeQuery($query, array $params = [], $types = [], $isSlave = false)
    {
        return $this->dbTraitDb->executeQuery($query, $params, $types, $isSlave);
    }

    /**
     * @deprecated
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
        return $this->dbTraitDb->insert($tableExpression, $data, $types);
    }

    /**
     * @deprecated
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
        return $this->dbTraitDb->update($tableExpression, $data, $identifier, $types);
    }

    /**
     * @deprecated
     * @param       $data
     * @param array $includeFields
     * @param array $excludeFields
     * @param array $cast
     * @return array
     */
    public function prepareMultipleValues($data, $includeFields = [], $excludeFields = [], $cast = [])
    {
        return $this->dbTraitDb->prepareMultipleValues($data, $includeFields, $excludeFields, $cast);
    }

    /**
     * @deprecated
     * @param false $isSlave
     */
    public function checkConnection($isSlave = false)
    {
        $this->dbTraitDb->checkConnection($isSlave);
    }
}
