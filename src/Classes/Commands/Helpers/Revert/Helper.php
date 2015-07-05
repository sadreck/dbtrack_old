<?php

namespace DBtrack\Commands\Helpers\Revert;

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

    public function __construct(Database $dbms, DBManager $dbManager) {
        $this->dbms = $dbms;
        $this->dbManager = $dbManager;
        $this->display = new UserInteraction();
    }

    public function revert(array $allRows) {
        foreach ($allRows as $row) {
            switch ($row->type) {
                case Database::TRIGGER_ACTION_INSERT:
                    $sql = "DELETE FROM {$row->table} WHERE {$row->primarycolumn} = :primary";
                    $this->dbms->executeQuery($sql, array('primary' => $row->primaryvalue));
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
                    $params = array();
                    foreach ($row->previous as $column => $value) {
                        $set[] = $column . ' = :' . $column;
                        $params[':' . $column] = $value;
                    }
                    $params['primarykeyvalue'] = $row->primaryvalue;

                    $sql = "UPDATE {$row->table} SET ". implode(', ', $set) ." WHERE {$row->primarycolumn} = :primarykeyvalue";
                    $this->dbms->executeQuery($sql, $params);
                    break;
            }
        }

        return true;
    }
}