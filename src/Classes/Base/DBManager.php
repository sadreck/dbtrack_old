<?php

namespace DBtrack\Base;

class DBManager {
    /** @var Database */
    protected $dbms = null;

    /**
     * Initialise Database Manager.
     * @param Database $dbms
     */
    public function __construct() {
        $this->dbms = AppHandler::getObject('Database');
    }

    /**
     * Get list of tables that will be tracked (the list items are the command line's arguments in --tables.
     * @param array $list
     * @return array
     */
    public function getTables(array $list) {
        $tables = array();

        // Get all tables from the database.
        $allTables = $this->dbms->getTables();

        // Check if the dbtrack tables are in there and remove them.
        foreach ($allTables as $index => $table) {
            if (substr($table, 0, 8) == 'dbtrack_') {
                unset($allTables[$index]);
            }
        }
        $allTables = array_values($allTables);

        // If there was no --tables argument passed, return all tables.
        if (count($list) == 0) {
            return $allTables;
        }

        // Parse passed tables (like wildcards).
        foreach ($list as $item) {
            // Check if we have any wildcards in the table name.
            if (stripos($item, '*') === false) {
                if (in_array($item, $allTables)) {
                    $tables[] = $item;
                }
            } else {
                // Clean parameter.
                $item = preg_replace('/[^\*A-Za-z0-9_-]/i', '', $item);
                if (!empty($item)) {
                    // Create regex.
                    $regex = '/' . str_replace('*', '.*', $item) . '/i';
                    $found = preg_grep($regex, $allTables);
                    if (count($found) > 0) {
                        $tables = array_merge($tables, $found);
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Create track tables if required.
     */
    public function prepareTrackTables() {
        if (!$this->dbms->tableExists('dbtrack_actions') &&
            !$this->dbms->tableExists('dbtrack_data') &&
            !$this->dbms->tableExists('dbtrack_keys')) {

            $sql = $this->dbms->loadSQLTemplate('tables');
            $this->dbms->executeScript($sql);

            if (!$this->dbms->tableExists('dbtrack_actions') ||
                !$this->dbms->tableExists('dbtrack_data') ||
                !$this->dbms->tableExists('dbtrack_keys')) {

                throw new \Exception('Could not create tracking tables.');
            }
        }
    }

    /**
     * Create triggers for the given tables.
     * @param array $tables
     */
    public function createTriggers(array $tables) {
        foreach ($tables as $table) {
            $this->dbms->createTrigger($table);

            $eventData = array(
                'event' => 'triggerCreated',
                'params' => (object)array(
                    'table' => $table
                )
            );
            Events::triggerEvent((object)$eventData);
        }
    }

    /**
     * Remove all dbtrack triggers from the database.
     */
    public function clearTriggers() {
        $triggers = $this->dbms->getTriggers();
        foreach ($triggers as $trigger) {
            if (substr($trigger, 0, 8) == 'dbtrack_') {
                $this->dbms->deleteTrigger($trigger);
            }
        }
    }

    /**
     * Check if there are triggers.
     * @return bool
     */
    public function hasTriggers() {
        $triggers = $this->dbms->getTriggers();
        foreach ($triggers as $trigger) {
            if (substr($trigger, 0, 8) == 'dbtrack_') {
                return true;
            }
        }
        return false;
    }

    /**
     * Commit current tracking data.
     * @param $groupId
     */
    public function commit($groupId) {
        $sql = "UPDATE dbtrack_actions SET groupid = :groupid WHERE groupid = 0";
        $this->dbms->executeQuery($sql, array('groupid' => $groupId));
        // Count new actions.
        $count = $this->dbms->getResult(
            'SELECT COALESCE(COUNT(id), 0) AS actions FROM dbtrack_actions WHERE groupid = :groupid',
            array('groupid' => $groupId)
        );
        return $count->actions;
    }

    /**
     * Drop track tables.
     */
    public function deleteTrackTables() {
        if ($this->dbms->tableExists('dbtrack_actions')) {
            $this->dbms->deleteTable('dbtrack_actions');
        }

        if ($this->dbms->tableExists('dbtrack_data')) {
            $this->dbms->deleteTable('dbtrack_data');
        }

        if ($this->dbms->tableExists('dbtrack_keys')) {
            $this->dbms->deleteTable('dbtrack_keys');
        }
    }

    /**
     * Check if the tracking tables exist.
     * @return bool
     */
    public function trackingTablesExist() {
        return $this->dbms->tableExists('dbtrack_actions') &&
                $this->dbms->tableExists('dbtrack_data') &&
                $this->dbms->tableExists('dbtrack_keys');
    }
}