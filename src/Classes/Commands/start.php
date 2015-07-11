<?php

namespace DBtrack\Commands;

use DBtrack\Base\AppHandler;
use DBtrack\Base\Command;

class start extends Command {
    public function execute() {
        $this->prepare();

        // Parse command line options.
        $options = $this->parseOptions();
        if (!isset($options['tables'])) {
            $options['tables'] = array();
        }

        $trackTables = $this->dbManager->getTables($options['tables']);
        if (count($trackTables) == 0) {
            throw new \Exception('Could not find any tables to track.');
        }

        if ($this->dbManager->hasTriggers()) {
            throw new \Exception('dbtrack is already running.');
        }

        if (isset($options['ignore-tables'])) {
            foreach ($options['ignore-tables'] as $table) {
                $index = array_search($table, $trackTables);
                if ($index !== false) {
                    unset($trackTables[$index]);
                }
            }
            $trackTables = array_values($trackTables);
        }

        // Prepare all required tables.
        $this->dbManager->prepareTrackTables();

        // Create triggers.
        $this->dbManager->createTriggers($trackTables);

        $this->userInteraction->outputMessage('Tracking running for '. count($trackTables) .' table(s)...');
        return true;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'start' . "\t\t" . 'Begin tracking.');
        } else {
            $this->userInteraction->outputMessage('start' . "\t\t" . 'Begins the tracking process.');

            $this->userInteraction->outputMessage("\t" . '--tables' . "\t\t" . 'Specify tables to be tracked (wildcards accepted).');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt start --tables table1 table2 tb*');
            $this->userInteraction->outputMessage("\t\t" . 'Track only tables table1, table2 and all tables that begin with <tb>');
            $this->userInteraction->outputMessage('');

            $this->userInteraction->outputMessage("\t" . '--ignore-tables' . "\t\t" . 'Specify tables to be ignored.');
            $this->userInteraction->outputMessage("\t" . 'Usage:');
            $this->userInteraction->outputMessage("\t\t" . 'dbt start --tignore-ables table1');
            $this->userInteraction->outputMessage("\t\t" . 'Track all tables except table1.');
            $this->userInteraction->outputMessage('');
        }
    }

    public function eventHandlers() {
        $events = array();

        $event = array(
            'event' => 'triggerCreated',
            'function' => __CLASS__ . '::eventTriggerCreated'
        );

        $events[] = (object)$event;

        return $events;
    }

    public static function eventTriggerCreated(\stdClass $params) {
        $display = AppHandler::getObject('UserInteraction');
        $display->outputMessage('.', false);
    }
}