<?php

namespace DBtrack\Commands\Helpers\Revert;

use DBtrack\Base\AppHandler;
use DBtrack\Base\Database;
use DBtrack\Base\DBManager;
use DBtrack\Base\UserInteraction;

class Helper {
    /** @var Database */
    protected $dbms = null;

    /** @var DBManager */
    protected $dbManager = null;

    /** @var UserInteraction */
    protected $display = null;

    public function __construct() {
        $this->dbms = AppHandler::getObject('Database');
        $this->dbManager = AppHandler::getObject('DBManager');
        $this->display = AppHandler::getObject('UserInteraction');
    }

    public function revert(array $allRows) {
        foreach ($allRows as $row) {
            $where = array();
            $params = array();
            foreach ($row->primaryKeys as $key) {
                $where[] = $key->name . ' = :' . $key->name;
                $params[$key->name] = $key->value;
            }
            $where = implode(' AND ', $where);

            switch ($row->type) {
                case Database::TRIGGER_ACTION_INSERT:
                    $sql = "DELETE FROM {$row->table} WHERE {$where}";
                    $this->dbms->executeQuery($sql, $params);
                    break;
                case Database::TRIGGER_ACTION_DELETE:
                    $data = (array)$row->data;
                    $columns = array_keys($data);
                    $values = $columns;
                    array_walk($values, function(&$value, &$key) {
                        $value = ':' . $value;
                    });

                    $sql = "INSERT INTO {$row->table} (". implode(', ', $columns) .") VALUES(". implode(', ', $values) .")";
                    $this->dbms->executeQuery($sql, $data);
                    break;
                case Database::TRIGGER_ACTION_UPDATE:
                    $set = array();
                    foreach ($row->previous as $column => $value) {
                        $set[] = $column . ' = :' . $column;
                        $params[':' . $column] = $value;
                    }

                    $sql = "UPDATE {$row->table} SET ". implode(', ', $set) ." WHERE {$where}";
                    $this->dbms->executeQuery($sql, $params);
                    break;
            }
        }

        return true;
    }
}