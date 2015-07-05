<?php

namespace DBtrack\Databases;

use DBtrack\Base\Database;

class pgsql extends Database {
    protected $type = 'pgsql';

    public function connect() {
        try {
            $dsn = "pgsql:host={$this->config->hostname};dbname={$this->config->database};user={$this->config->username};password={$this->config->password};port=5432";
            $this->connection = new \PDO($dsn);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }


    public function getTables() {
        $sql = "SELECT table_name
                FROM information_schema.tables
                WHERE table_catalog = :schema AND table_schema = 'public' AND table_type = 'BASE TABLE'
                ORDER BY table_name;";
        return $this->getResults($sql, array('schema' => $this->config->database), \PDO::FETCH_COLUMN);
    }

    public function getTableColumns($table) {
        $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_catalog = :schema AND table_schema = 'public' AND table_name = :table
                ORDER BY ordinal_position";
        return $this->getResults($sql, array('table' => $table, 'schema' => $this->config->database), \PDO::FETCH_COLUMN);
    }

    public function getTriggers() {
        $sql = "SELECT trigger_name
                FROM information_schema.triggers
                WHERE trigger_catalog = :schema AND trigger_schema = 'public'
                ORDER BY trigger_name";
        $statement = $this->connection->prepare($sql);
        $statement->bindParam(':schema', $this->config->database);
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function deleteTrigger($name) {
        $table = $this->getTriggerTable($name);
        if (empty($table)) {
            return false;
        }
        $table = reset($table);

        $statement = $this->connection->query("DROP TRIGGER IF EXISTS {$name} ON {$table}");
        $statement = $this->connection->query("DROP FUNCTION IF EXISTS {$name}_function()");
    }

    protected function getTriggerTable($name) {
        $sql = "SELECT event_object_table
                FROM information_schema.triggers
                WHERE trigger_catalog = :schema AND trigger_schema = 'public' AND trigger_name = :trigger";
        $statement = $this->connection->prepare($sql);
        $statement->bindParam(':schema', $this->config->database);
        $statement->bindParam(':trigger', $name);
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function deleteTable($table) {
        $statement = $this->connection->query("DROP TABLE {$table}");
    }

    public function getPrimaryKey($table) {
        $sql = "SELECT kcu.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON kcu.table_catalog = tc.table_catalog AND kcu.table_schema = tc.table_schema AND kcu.table_name = tc.table_name
                WHERE tc.constraint_type = 'PRIMARY KEY'
                      AND tc.table_catalog = :schema
                      AND tc.table_schema = 'public'
                      AND tc.table_name = :table";
        $results = $this->getResults($sql, array('schema' => $this->config->database, 'table' => $table));
        $columns = array();
        if (count($results) == 1) {
            $columns = array($results[0]->column_name);
        } else {
            foreach ($results as $result) {
                $columns[] = $result->column_name;
            }
        }

        return $columns;
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
                          VALUES(lastid, '{$column}', NEW.{$column});";
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
        $this->executeScript($sql);
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
                          VALUES(lastid, '{$column}', OLD.{$column});";
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
        $this->executeScript($sql);
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
            $inserts[] = "IF OLD.{$column} <> NEW.{$column} THEN
                            INSERT INTO dbtrack_data(actionid, columnname, databefore, dataafter)
                            VALUES(lastid, '{$column}', OLD.{$column}, NEW.{$column});
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
        $this->executeScript($sql);
    }
}