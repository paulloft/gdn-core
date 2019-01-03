<?php

namespace Garden\Db\Driver;

use \Garden\Exception;
use \Garden\Db\Database;

/**
 * PDO database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class PDO extends SQL {

    // PDO uses no quoting for identifiers
    protected $_identifier = '';

    /**
     * @var \PDO
     */
    protected $_connection;

    public function __construct($name, array $config)
    {
        parent::__construct($name, $config);

        if (isset($this->_config['identifier'])) {
            // Allow the identifier to be overloaded per-connection
            $this->_identifier = (string)$this->_config['identifier'];
        }
    }


    public function connect()
    {
        if ($this->_connection) {
            return;
        }

        // Extract the connection parameters, adding required variabels
        $host = $this->_config['host'] ?? 'localhost';
        $type = $this->_config['type'] ?? 'mysql';
        $dsn = $this->_config['dsn'] ?? null;
        $database = $this->_config['database'] ?? null;
        $username = $this->_config['username'] ?? null;
        $password = $this->_config['password'] ?? null;
        $persistent = $this->_config['persistent'] ?? null;
        $options = $this->_config['options'] ?? [];

        if (!$dsn) {
            $dsn = "$type:host=$host;dbname=$database";
        }

        // Force PDO to use exceptions for all errors
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        if (!empty($persistent)) {
            // Make the connection persistent
            $options[\PDO::ATTR_PERSISTENT] = true;
        }

        try {
            // Create a new PDO connection
            $this->_connection = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new Exception\Database($e->getMessage(), $e->getCode());
        }

        if (!empty($this->_config['charset'])) {
            // Set the character set
            $this->set_charset($this->_config['charset']);
        }
    }

    /**
     * Create or redefine a SQL aggregate function.
     *
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreateaggregate
     *
     * @param   string $name Name of the SQL function to be created or redefined
     * @param   callback $step Called for each row of a result set
     * @param   callback $final Called after all rows of a result set have been processed
     * @param   integer $arguments Number of arguments that the SQL function takes
     *
     * @return  boolean
     */
    public function create_aggregate($name, $step, $final, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateAggregate($name, $step, $final, $arguments);
    }

    /**
     * Create or redefine a SQL function.
     *
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreatefunction
     *
     * @param   string $name Name of the SQL function to be created or redefined
     * @param   callback $callback Callback which implements the SQL function
     * @param   integer $arguments Number of arguments that the SQL function takes
     *
     * @return  boolean
     */
    public function create_function($name, $callback, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateFunction($name, $callback, $arguments);
    }

    public function disconnect()
    {
        // Destroy the PDO object
        $this->_connection = null;

        return parent::disconnect();
    }

    public function set_charset($charset)
    {
        // Make sure the database is connected
        $this->_connection OR $this->connect();

        // This SQL-92 syntax is not supported by all drivers
        $this->_connection->exec('SET NAMES ' . $this->quote($charset));
    }

    /**
     * @param int $type
     * @param string $sql
     * @param bool $asObject
     * @param array|null $params
     * @return array|Database\Result\Cached|object
     * @throws Exception\Database
     */
    public function query($type, $sql, $asObject = false, array $params = null)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        try {
            $result = $this->_connection->query($sql);
        } catch (\Exception $e) {
            // Convert the exception in a database exception
            throw new Exception\Database("{$e->getMessage()} \n[$sql ]");
        }

        // Set the last query
        $this->last_query = $sql;

        if ($type === Database::SELECT) {
            // Convert the result into an array, as PDOStatement::rowCount is not reliable
            if ($asObject === false) {
                $result->setFetchMode(\PDO::FETCH_ASSOC);
            } elseif (is_string($asObject)) {
                $result->setFetchMode(\PDO::FETCH_CLASS, $asObject, $params);
            } else {
                $result->setFetchMode(\PDO::FETCH_CLASS, 'stdClass');
            }

            $result = $result->fetchAll();

            // Return an iterator of results
            return new Database\Result\Cached($result, $sql, $asObject);
        }

        if ($type === Database::INSERT) {
            // Return a list of insert id and rows created
            return [
                $this->_connection->lastInsertId(),
                $result->rowCount()
            ];
        }

        // Return the number of rows affected
        return $result->rowCount();
    }

    public function begin($mode = null)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->beginTransaction();
    }

    public function commit()
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->commit();
    }

    public function rollback()
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->rollBack();
    }

    public function escape($value)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->quote($value);
    }

}
