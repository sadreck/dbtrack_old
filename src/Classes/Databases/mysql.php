<?php

namespace DBtrack\Databases;

use DBtrack\Base\Database;

class mysql extends Database {

    protected $type = 'mysql';

    public function connect() {
        try {
            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            );
            $dsn = "mysql:host={$this->config->hostname};dbname={$this->config->database}";
            $this->connection = new \PDO($dsn, $this->config->username, $this->config->password, $options);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getTables() {
        $statement = $this->connection->query('SHOW TABLES');
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getTableColumns($table) {
        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = :table AND TABLE_SCHEMA = :schema
                ORDER BY ORDINAL_POSITION";
        return $this->getResults($sql, array('table' => $table, 'schema' => $this->config->database), \PDO::FETCH_COLUMN);
    }

    public function createTrigger($table) {
        $columns = $this->getTableColumns($table);
        $primary = $this->getPrimaryKey($table);

        $this->_createTrigger('dbtrack_' . $table . '_insert', $table, Database::TRIGGER_ACTION_INSERT, $columns, $primary);
        $this->_createTrigger('dbtrack_' . $table . '_delete', $table, Database::TRIGGER_ACTION_DELETE, $columns, $primary);
        $this->_createTrigger('dbtrack_' . $table . '_update', $table, Database::TRIGGER_ACTION_UPDATE, $columns, $primary);
    }

    private function _createTrigger($name, $table, $action, array $columns, array $primary) {
        $sqlTemplate = $this->loadSQLTemplate('trigger.template');

        $state = (Database::TRIGGER_ACTION_DELETE) ? 'OLD' : 'NEW';

        // Create the INSERT query for the primary keys.
        $primaryKeys = array();
        foreach ($primary as $key) {
            $primaryKeys[] = "INSERT INTO dbtrack_keys(actionid, name, value)
                              VALUES(@lastid, '{$key}', {$state}.{$key});";
        }

        // Create the INSERT query for the actual data that changed.
        $inserts = array();
        foreach ($columns as $column) {
            switch ($action) {
                case Database::TRIGGER_ACTION_INSERT:
                    $inserts[] = "INSERT INTO dbtrack_data(actionid, columnname, dataafter)
                                  VALUES(@lastid, '{$column}', {$state}.{$column});";
                    break;
                case Database::TRIGGER_ACTION_UPDATE:
                    $inserts[] = "IF (OLD.{$column} <> NEW.{$column}) THEN
                                    INSERT INTO dbtrack_data(actionid, columnname, databefore, dataafter)
                                    VALUES(@lastid, '{$column}', OLD.{$column}, NEW.{$column});
                                  END IF;";
                    break;
                case Database::TRIGGER_ACTION_DELETE:
                    $inserts[] = "INSERT INTO dbtrack_data(actionid, columnname, databefore)
                                  VALUES(@lastid, '{$column}', {$state}.{$column});";
                    break;
            }

        }


        // Replace variables.
        $sql = str_replace(
            array(
                '%NAME%',
                '%TYPE',
                '%TABLE%',
                '%ACTION%',
                '%PRIMARYKEYS%',
                '%INSERTS%'
            ),
            array(
                $name,
                $this->actionDescriptions[$action],
                $table,
                $action,
                implode(PHP_EOL, $primaryKeys),
                implode(PHP_EOL, $inserts)
            ),
            $sqlTemplate
        );

        $this->executeQuery($sql);
    }

    public function getTriggers() {
        $statement = $this->connection->query('SHOW TRIGGERS');
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function deleteTrigger($name) {
        $statement = $this->connection->query("DROP TRIGGER {$name}");
    }

    public function deleteTable($table) {
        $statement = $this->connection->query("DROP TABLE {$table}");
    }

    public function getPrimaryKey($table) {
        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_KEY = 'PRI'";
        $results = $this->getResults($sql, array('schema' => $this->config->database, 'table' => $table));
        $columns = array();
        if (count($results) == 1) {
            $columns = array($results[0]->COLUMN_NAME);
        } else {
            foreach ($results as $result) {
                $columns[] = $result->COLUMN_NAME;
            }
        }

        return $columns;
    }
}