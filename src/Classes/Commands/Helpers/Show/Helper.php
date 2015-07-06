<?php

namespace DBtrack\Commands\Helpers\Show;

use DBtrack\Base\ActionParser;
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

    /** @var ActionParser */
    protected $actionParser = null;

    /** @var int */
    protected $valueDisplayLength = 20;

    /** @var array */
    protected $filterActions = array();

    /** @var array */
    protected $filterTables = array();

    /** @var array */
    protected $ignoreTables = array();

    public function __construct(Database $dbms, DBManager $dbManager) {
        $this->dbms = $dbms;
        $this->dbManager = $dbManager;
        $this->display = new UserInteraction();
        $this->actionParser = new ActionParser($this->dbms, $this->dbManager);
        $this->resetFilterActions();
    }

    /**
     * Main function that processes and displays tracking information.
     * @param $groupId
     * @throws \Exception
     */
    public function show($groupId) {
        if (!$this->actionParser->groupExists($groupId)) {
            throw new \Exception('Group ID does not exist: ' . $groupId);
        }

        $actions = $this->actionParser->parseGroup($groupId);
        $fullRows = $this->actionParser->getFullRows($actions);
        $fullRows = $this->filterRows($fullRows);
        $this->displayRows($fullRows, $actions);
    }

    /**
     * Export stats.
     * @param $groupId
     * @param $type
     * @param $saveTo
     * @return bool
     * @throws \Exception
     */
    public function export($groupId, $type, $saveTo) {
        if (!$this->actionParser->groupExists($groupId)) {
            throw new \Exception('Group ID does not exist: ' . $groupId);
        }

        if ($type != 'csv') {
            throw new \Exception('Unknown export type: ' . $type);
        }

        $actions = $this->actionParser->parseGroup($groupId);
        $fullRows = $this->actionParser->getFullRows($actions);
        $fullRows = $this->filterRows($fullRows);

        $this->exportToCSV($fullRows, $saveTo);

        $this->display->outputMessage('Export saved to: ' . $saveTo);

        return true;
    }

    /**
     * Export to CSV.
     * @param array $fullRows
     * @param $saveTo
     * @throws \Exception
     */
    protected function exportToCSV(array $fullRows, $saveTo) {
        if (file_exists($saveTo)) {
            @unlink($saveTo);
            if (file_exists($saveTo)) {
                throw new \Exception('Could not delete file: ' . $saveTo);
            }
        }

        $fp = fopen($saveTo, 'w');
        if (!$fp) {
            throw new \Exception('Could not open file: ' . $saveTo);
        }
        foreach ($fullRows as $row) {
            // First column is the row's table and then all the columns.
            $header = array_merge(array($row->table), array_keys((array)$row->data));

            // Add action type as the first column in the data row.
            $line = array($this->dbms->getTrackTypeDescription($row->type));

            $values = array_values((array)$row->data);
            $line = array_merge($line, $values);

            fputcsv($fp, $header);
            fputcsv($fp, $line);
            fputcsv($fp, array());
        }

        fclose($fp);
    }

    /**
     * Filter tracking rows.
     * @param array $fullRows
     * @return array
     */
    protected function filterRows(array $fullRows) {
        $cleanRows = array();

        foreach ($fullRows as $row) {
            // Check filtered actions.
            if (!in_array($row->type, $this->filterActions)) {
                continue;
            }

            // Check if we need to ignore the table.
            if (count($this->ignoreTables) > 0) {
                if (in_array($row->table, $this->ignoreTables)) {
                    continue;
                }
            }

            // Check if we want to filter the table.
            if (count($this->filterTables) > 0) {
                if (!in_array($row->table, $this->filterTables)) {
                    continue;
                }
            }

            $cleanRows[] = $row;
        }

        return $cleanRows;
    }

    /**
     * Display final output.
     * @param array $fullRows
     * @param array $actions
     * @throws \Exception
     */
    protected function displayRows(array $fullRows, array $actions) {
        // The position is the same for $fullRows and $changedRows (for cross referencing purposes).
        $rowCount = 0;
        foreach ($fullRows as $row) {
            // First column is the row's table and then all the columns.
            $header = array_merge(array($row->table), array_keys((array)$row->data));

            // Add action type as the first column in the data row.
            $line = array($this->dbms->getTrackTypeDescription($row->type));
            $padding = strlen($header[0]) > strlen($line[0]) ? array(strlen($header[0])) : array(strlen($line[0]));

            // Column counter.
            $pos = 0;
            foreach ($row->data as $column => $value) {
                ++$pos;

                if ($this->valueDisplayLength > 0 && strlen($value) > $this->valueDisplayLength) {
                    $value = substr($value, 0, 20) . '...';
                }

                // Make sure the column's display width is long enough.
                $padding[] = strlen($value) > strlen($header[$pos]) ? strlen($value) : strlen($header[$pos]);

                // Highlight only changed columns.
                if ($row->type == Database::TRIGGER_ACTION_UPDATE) {
                    $isPrimaryColumn = false;
                    foreach ($row->primaryKeys as $key) {
                        if ($key->name == $column) {
                            $isPrimaryColumn = true;
                            break;
                        }
                    }

                    if (isset($actions[$rowCount]->data->{$column}) && !$isPrimaryColumn) {
                        $value = $this->display->getColourText($value, 'yellow');
                    }
                }
                $line[] = $value;
            }

            // Display line.
            $this->display->setHeader($header);
            $this->display->setPadding($padding);
            $this->display->showHeader();
            $this->display->showLine($line);
            $this->display->showLine();

            ++$rowCount;
        }
    }

    /**
     * Set the maximum length of a displayed value.
     * @param $length
     */
    public function setValueDisplayLength($length) {
        $this->valueDisplayLength = $length;
    }

    /**
     * Set actions to filter.
     * @param array $actions
     */
    public function setFilterActions(array $actions) {
        $this->filterActions = $actions;
    }

    /**
     * Set ignored actions (filter).
     * @param array $actions
     */
    public function setFilterIgnoreActions(array $actions) {
        foreach ($actions as $action) {
            $key = array_search($action, $this->filterActions);
            if ($key !== false) {
                unset($this->filterActions[$key]);
            }
        }
    }

    /**
     * Reset action filters.
     */
    public function resetFilterActions() {
        $this->filterActions = array(
            Database::TRIGGER_ACTION_INSERT,
            Database::TRIGGER_ACTION_UPDATE,
            Database::TRIGGER_ACTION_DELETE
        );
    }

    /**
     * Reset table filters.
     */
    public function resetFilterTables() {
        $this->filterTables = array();
        $this->ignoreTables = array();
    }

    /**
     * Set tables to filter.
     * @param array $tables
     */
    public function setFilterTables(array $tables) {
        $this->filterTables = $tables;
    }

    /**
     * Set tables to ignore (filter).
     * @param array $tables
     */
    public function setFilterIgnoreTables(array $tables) {
        $this->ignoreTables = $tables;
    }
}