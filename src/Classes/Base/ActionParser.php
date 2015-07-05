<?php

namespace DBtrack\Base;

class ActionParser {
    /** @var Database */
    protected $dbms = null;

    /** @var DBManager */
    protected $dbManager = null;

    /** @var array Table cache. */
    private $_tables = array();

    public function __construct(Database $dbms, DBManager $dbManager) {
        $this->dbms = $dbms;
        $this->dbManager = $dbManager;
    }

    /**
     * Check if the group id passed exists in the tracking table.
     * @param $groupId
     * @return bool
     */
    public function groupExists($groupId) {
        $sql = "SELECT COUNT(id) AS c FROM dbtrack_actions WHERE groupid = :groupid";
        $result = $this->dbms->getResult($sql, array('groupid' => $groupId));
        if (!empty($result) && $result->c > 0) {
            return true;
        }
        return false;
    }

    public function parseGroup($groupId) {
        // Get all actions for the group.
        $sql = "SELECT id, tablename, actiontype AS type
                FROM dbtrack_actions
                WHERE groupid = :groupid
                ORDER BY id DESC";
        $results = $this->dbms->getResults($sql, array('groupid' => $groupId));

        $actions = array();
        foreach ($results as $result) {
            $action = $this->parseAction($result, $actions);
            if ($action === false) {
                continue;
            }
            array_unshift($actions, $action);
        }

        return $actions;
    }

    /**
     * Fill missing columns for the parsed actions.
     * @param array $actions
     * @return array
     */
    public function getFullRows(array $actions) {
        $fullRows = array();

        foreach ($actions as $action) {
            if ($action->type == Database::TRIGGER_ACTION_INSERT || $action->type == Database::TRIGGER_ACTION_DELETE) {
                $fullRows[] = $action;
            } else {
                $row = $this->getPreviousRecord($actions, $action->table, $action->primarycolumn, $action->primaryvalue);
                $newAction = clone $action;
                $data = clone $action->data;
                foreach ($row as $column => $value) {
                    if (!isset($data->{$column})) {
                        $data->{$column} = $value;
                    }
                    $newAction->data = $data;
                }
                $fullRows[] = $newAction;
            }
        }

        return $this->sortRowColumns($fullRows);
    }

    /**
     * Sort row columns.
     * @param array $fullRows
     * @return array
     */
    protected function sortRowColumns(array $fullRows) {
        foreach ($fullRows as $index => $row) {
            if (!isset($this->_tables[$row->table])) {
                $this->_tables[$row->table] = $this->dbms->getTableColumns($row->table);
            }

            $new = new \stdClass();
            foreach ($this->_tables[$row->table] as $column) {
                $new->{$column} = $row->data->{$column};
            }
            $fullRows[$index]->data = $new;
        }

        return $fullRows;
    }

    /**
     * Parse tracking action.
     * @param \stdClass $action
     * @param array $allActions
     * @return array|bool|mixed|\stdClass
     * @throws \Exception
     */
    protected function parseAction(\stdClass $action, array $allActions) {
        // Check if all required properties are set.
        if (!isset($action->id, $action->tablename, $action->type)) {
            return false;
        }

        // Get all records/columns that changed.
        $sql = "SELECT dbd.columnname, dbd.databefore, dbd.dataafter, dba.primarycolumn, dba.primaryvalue
                FROM dbtrack_actions dba
                JOIN dbtrack_data dbd ON dbd.actionid = dba.id
                WHERE dba.id = :id AND dba.actiontype = :type
                ORDER BY dbd.id";
        $results = $this->dbms->getResults($sql, array('id' => $action->id, 'type' => $action->type));
        if (count($results) == 0) {
            // This happens if there is an 'update' query but not values have been updated (all new values match the old ones).
            return false;
        }

        $data = new \stdClass();
        $previous = new \stdClass();
        $primaryColumn = '';
        $primaryValue = '';
        foreach ($results as $result) {
            if (empty($primaryColumn)) {
                $primaryColumn = $result->primarycolumn;
                $primaryValue = $result->primaryvalue;
            }

            if ($action->type == Database::TRIGGER_ACTION_INSERT) {
                $data->{$result->columnname} = $result->dataafter;
            } else if ($action->type == Database::TRIGGER_ACTION_DELETE) {
                $data->{$result->columnname} = $result->databefore;
            } else {
                // Check if object is empty.
                if ($data == new \stdClass()) {
                    $data = $this->getPreviousRecord($allActions, $action->tablename, $result->primarycolumn, $result->primaryvalue);

                    // If object is still empty, we 've got a problem.
                    if ($data == new \stdClass()) {
                        throw new \Exception('Could not query the database row for the previous state of record: ', print_r($result, true));
                    }
                    // Cleanup data (remove columns that may have been assigned in previous tracking data).
                    $data = $this->cleanRecord($results, $data);
                }

                $data->{$result->columnname} = $result->dataafter;
                $previous->{$result->columnname} = $result->databefore;
            }
        }

        $return = new \stdClass();
        $return->type = $action->type;
        $return->table = $action->tablename;
        $return->primarycolumn = $primaryColumn;
        $return->primaryvalue = $primaryValue;
        $return->data = $data;
        $return->previous = $previous;

        return $return;
    }

    /**
     * Clean the $data class from any columns that are not present in $results.
     * @param array $results
     * @param \stdClass $data
     * @return \stdClass
     */
    protected function cleanRecord(array $results, \stdClass $data) {
        // Gather all column names.
        $columns = array();
        foreach ($results as $result) {
            $columns[$result->columnname] = true;
            //$columns[$result->primarycolumn] = true;
        }

        // Cleanup current record.
        foreach ($data as $column => $value) {
            if (!isset($columns[$column])) {
                unset($data->{$column});
            }
        }

        return $data;
    }

    /**
     * Get the previous data of the record either from the array that's already been processed or directly from the database.
     * @param array $allActions
     * @param $tableName
     * @param $primaryColumn
     * @param $primaryValue
     * @return array|mixed|\stdClass
     */
    protected function getPreviousRecord(array $allActions, $tableName, $primaryColumn, $primaryValue) {
        $data = new \stdClass();
        foreach ($allActions as $action) {
            if ($action->table == $tableName) {
                if (isset($action->data->{$primaryColumn}) && $action->data->{$primaryColumn} == $primaryValue) {
                    $data = clone $action->data;
                    break;
                }
            }
        }

        // Check if record was not found.
        if ($data == new \stdClass()) {
            $data = $this->queryPreviousRecord($tableName, $primaryColumn, $primaryValue);
        }

        return $data;
    }

    /**
     * Get previous state of a row directly from the database.
     * @param $table
     * @param $primaryColumn
     * @param $primaryValue
     * @return array|mixed
     * @internal param \stdClass $row
     */
    protected function queryPreviousRecord($table, $primaryColumn, $primaryValue) {
        $sql = "SELECT * FROM {$table} WHERE {$primaryColumn} = :primary";
        return $this->dbms->getResult($sql, array('primary' => $primaryValue));
    }

    /**
     * Get all groups after the specified one.
     * @param $groupId
     * @return array
     */
    public function getAllGroups($groupId) {
        $sql = "SELECT groupid AS id
                FROM dbtrack_actions
                WHERE groupid >= :groupid
                GROUP BY groupid
                ORDER BY groupid DESC";
        return $this->dbms->getResults($sql, array('groupid' => $groupId));
    }
}