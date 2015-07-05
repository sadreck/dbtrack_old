<?php

namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Commands\Helpers\Show\Helper;

class show extends Command {

    /** @var Helper */
    protected $helper = null;

    public function execute() {
        $this->prepare();

        if (!$this->dbManager->trackingTablesExist()) {
            throw new \Exception('Tracking tables do not exist. Use <dbt start> to start.');
        }

        $this->helper = new Helper($this->dbms, $this->dbManager);

        $options = $this->parseOptions($this->options);
        if (!isset($options[0]) || empty($options[0])) {
            throw new \Exception('No dbtrack group id specified. Use <dbt stats> to get one.');
        }

        if (isset($options['full'])) {
            $this->helper->setValueDisplayLength(0);
        }

        if (isset($options['actions'])) {
            $this->helper->setFilterActions($options['actions']);
        }

        if (isset($options['ignore-actions'])) {
            $this->helper->setFilterIgnoreActions($options['ignore-actions']);
        }

        if (isset($options['tables'])) {
            $this->helper->setFilterTables($options['tables']);
        }

        if (isset($options['ignore-tables'])) {
            $this->helper->setFilterIgnoreTables($options['ignore-tables']);
        }

        if (isset($options['export'], $options['export-path'])) {
            $type = reset($options['export']);
            $saveTo = reset($options['export-path']);

            $this->helper->export($options[0], $type, $saveTo);
            return true;
        }

        $this->helper->show($options[0]);
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'show' . "\t\t" . 'Display tracked actions.');
        } else {
            $this->userInteraction->outputMessage('show' . "\t\t" . 'Display tracked actions');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage('Available arguments:');
            $this->userInteraction->outputMessage("\t" . '--full' . "\t\t\t" . 'By default only 20 first characters of a column are displayed for its value. Using --full the whole value is returned.');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--actions' . "\t\t" . 'Filter only specific actions. (1 = INSERT, 2 = UPDATE, 3 = DELETE)');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt show --actions 1 2');
            $this->userInteraction->outputMessage("\t\t" . 'Display tracking information for INSERT/UPDATE.');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--ignore-actions' . "\t" . 'Filter out specific actions. (1 = INSERT, 2 = UPDATE, 3 = DELETE)');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt show --ignore-actions 1');
            $this->userInteraction->outputMessage("\t\t" . 'Display tracking information for UPDATE/DELETE but not INSERT.');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--tables' . "\t\t" . 'Filter only specific tables.');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt show --tables table1 table2 table3');
            $this->userInteraction->outputMessage("\t\t" . 'Display tracking information only for tables table1, table2 and table3.');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--ignore-tables' . "\t" . 'Filter out specific tables.');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt show --ignore-tables table2');
            $this->userInteraction->outputMessage("\t\t" . 'Display tracking information for all tables except table2.');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--export' . "\t" . 'Export output to CSV');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt show --export csv --export-path /tmp/output.csv');
            $this->userInteraction->outputMessage("\t\t" . 'Exports output to the specified file.');
            $this->userInteraction->outputMessage('');
        }
    }
}