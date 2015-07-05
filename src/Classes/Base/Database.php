<?php

namespace DBtrack\Base;

abstract class Database {
    /** @var \stdClass */
    protected $config = null;

    /** @var \PDO */
    protected $connection = null;

    /** @var string */
    protected $type = '';

    /**
     * Set descriptions for tracking actions.
     * @var array
     */
    protected $actionDescriptions = array(
        self::TRIGGER_ACTION_INSERT => 'INSERT',
        self::TRIGGER_ACTION_UPDATE => 'UPDATE',
        self::TRIGGER_ACTION_DELETE => 'DELETE',
    );

    /**
     * Constants.
     */
    const TRIGGER_ACTION_INSERT = 1;
    const TRIGGER_ACTION_UPDATE = 2;
    const TRIGGER_ACTION_DELETE = 3;

    /**
     * Initialise database handler.
     * @param $hostname
     * @param $database
     * @param $username
     * @param $password
     * @throws \Exception
     */
    public function __construct($hostname, $database, $username, $password) {
        $this->config = new \stdClass();
        $this->config->hostname = $hostname;
        $this->config->database = $database;
        $this->config->username = $username;
        $this->config->password = $password;

        if (empty($this->type)) {
            throw new \Exception('Database type not set.');
        }
    }

    /**
     * Check if a given table exists in the database.
     * @param $table
     * @return bool
     */
    public function tableExists($table) {
        $tables = $this->getTables();
        return in_array($table, $tables);
    }

    /**
     * Return database type.
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Execute a given SQL query.
     * @param $sql
     * @param array $params
     */
    public function executeQuery($sql, array $params = array()) {
        $statement = $this->connection->prepare($sql);
        foreach ($params as $variable => $value) {
            $statement->bindValue($variable, $value);
        }
        $statement->execute();
    }

    /**
     * Get all results from a query.
     * @param $sql
     * @param array $params
     * @return array
     */
    public function getResults($sql, $params = array(), $fetchType = \PDO::FETCH_CLASS) {
        if (count($params) == 0) {
            $statement = $this->connection->query($sql);
        } else {
            $statement = $this->connection->prepare($sql);
            foreach ($params as $name => $value) {
                $statement->bindValue($name, $value);
            }
            $statement->execute();
        }

        return $statement->fetchAll($fetchType);
    }

    /**
     * Get a single result.
     * @param $sql
     * @param array $params
     * @return array|mixed
     */
    public function getResult($sql, $params = array()) {
        $results = $this->getResults($sql, $params);
        if (count($results) > 0) {
            return reset($results);
        }
        return array();
    }

    /**
     * Return the description for the track type.
     * @param $type
     * @return string
     */
    public function getTrackTypeDescription($type) {
        return isset($this->actionDescriptions[$type]) ? $this->actionDescriptions[$type] : '';
    }

    /**
     * Load an SQL file template.
     * @param $fileName
     * @return string
     * @throws \Exception
     */
    public function loadSQLTemplate($fileName) {
        $filePath = ROOT . "/Classes/Databases/SQL/{$this->type}/{$fileName}.sql";
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("File {$filePath} does not exist or is not readable.");
        }
        return file_get_contents($filePath);
    }

    /**
     * Execute a whole SQL script.
     * @param $sqlScript
     */
    public function executeScript($sqlScript) {
        $query = $this->connection->exec($sqlScript);
    }

    /**
     * Begin database transaction.
     */
    public function beginTransaction() {
        if (!$this->connection->inTransaction()) {
            $this->connection->beginTransaction();
        }
    }

    /**
     * Rollback database transaction.
     */
    public function rollbackTransaction() {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }

    /**
     * Commit database transaction.
     */
    public function commitTransaction() {
        if ($this->connection->inTransaction()) {
            $this->connection->commit();
        }
    }

    /**
     * If true the PDO connection must be stored in $this->connection.
     * @return bool
     */
    abstract public function connect();

    /**
     * Must return a table list within an array.
     * @return array
     */
    abstract public function getTables();

    /**
     * Get all table's columns in an array.
     * @param $table
     * @return array
     */
    abstract public function getTableColumns($table);

    /**
     * Create a tracking trigger for the given table.
     * @param $table
     * @return mixed
     */
    abstract public function createTrigger($table);

    /**
     * Must return a trigger list within an array.
     * @return array
     */
    abstract public function getTriggers();

    /**
     * Delete given trigger.
     * @param $name
     * @return mixed
     */
    abstract public function deleteTrigger($name);

    /**
     * Delete given table.
     * @param $table
     * @return mixed
     */
    abstract public function deleteTable($table);

    /**
     * Retrieve primary key for the given table.
     * @param $table
     * @return mixed
     */
    abstract public function getPrimaryKey($table);
}