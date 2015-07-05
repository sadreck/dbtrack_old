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

        if (count($primary) > 1) {
            throw new \Exception('Primary keys with multiple fields are not supported (yet). Table: ' . $table);
        }

        $this->_createTriggerInsert('dbtrack_' . $table . '_insert', $table, $columns, $primary);
        $this->_createTriggerDelete('dbtrack_' . $table . '_delete', $table, $columns, $primary);
        $this->_createTriggerUpdate('dbtrack_' . $table . '_update', $table, $columns, $primary);
    }

    /**
     * Create a trigger for INSERT.
     * @param $name
     * @param $table
     * @param array $columns
     */
    private function _createTriggerInsert($name, $table, array $columns, $primary) {
        $sqlTemplate = $this->loadSQLTemplate('trigger.insert');
        if (count($primary) == 1) {
            $primary = reset($primary);
        }

        $inserts = array();
        foreach ($columns as $column) {
            $inserts[] = "INSERT INTO dbtrack_data(actionid, columnname, dataafter)
                          VALUES(@id, '{$column}', NEW.{$column});";
        }
        $inserts = implode(PHP_EOL, $inserts);

        $sql = str_replace(
            array(
                '%NAME%',
                '%TABLE%',
                '%ACTION%',
                '%PRIMARY%',
                '%INSERTS%'
            ),
            array(
                $name,
                $table,
                self::TRIGGER_ACTION_INSERT,
                $primary,
                $inserts
            ),
            $sqlTemplate
        );
        $this->executeQuery($sql);
    }

    /**
     * Create a trigger for DELETE.
     * @param $name
     * @param $table
     * @param array $columns
     */
    private function _createTriggerDelete($name, $table, array $columns, $primary) {
        $sqlTemplate = $this->loadSQLTemplate('trigger.delete');
        if (count($primary) == 1) {
            $primary = reset($primary);
        }

        $inserts = array();
        foreach ($columns as $column) {
            $inserts[] = "INSERT INTO dbtrack_data(actionid, columnname, databefore)
                          VALUES(@id, '{$column}', OLD.{$column});";
        }
        $inserts = implode(PHP_EOL, $inserts);

        $sql = str_replace(
            array(
                '%NAME%',
                '%TABLE%',
                '%ACTION%',
                '%PRIMARY%',
                '%INSERTS%'
            ),
            array(
                $name,
                $table,
                self::TRIGGER_ACTION_DELETE,
                $primary,
                $inserts
            ),
            $sqlTemplate
        );
        $this->executeQuery($sql);
    }

    /**
     * Create a trigger for UPDATE.
     * @param $name
     * @param $table
     * @param array $columns
     */
    private function _createTriggerUpdate($name, $table, array $columns, $primary) {
        $sqlTemplate = $this->loadSQLTemplate('trigger.update');
        if (count($primary) == 1) {
            $primary = reset($primary);
        }

        $inserts = array();
        foreach ($columns as $column) {
            $inserts[] = "IF (OLD.{$column} <> NEW.{$column}) THEN
                            INSERT INTO dbtrack_data(actionid, columnname, databefore, dataafter)
                            VALUES(@id, '{$column}', OLD.{$column}, NEW.{$column});
                          END IF;";
        }
        $inserts = implode(PHP_EOL, $inserts);

        $sql = str_replace(
            array(
                '%NAME%',
                '%TABLE%',
                '%ACTION%',
                '%PRIMARY%',
                '%INSERTS%'
            ),
            array(
                $name,
                $table,
                self::TRIGGER_ACTION_UPDATE,
                $primary,
                $inserts
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