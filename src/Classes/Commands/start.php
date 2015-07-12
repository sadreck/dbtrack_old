<?php

namespace DBtrack\Commands;

use DBtrack\Base\ChainManager;
use DBtrack\Base\Command;

class start extends Command {
    public function execute() {
        $this->prepare();

        // Parse command line options.
        $options = $this->parseOptions();
        if (!isset($options['tables'])) {
            $options['tables'] = array();
        }

        if ($this->dbManager->hasTriggers()) {
            throw new \Exception('dbtrack is already running.');
        }

        $trackTables = $this->dbManager->getTables($options['tables']);
        if (count($trackTables) == 0) {
            throw new \Exception('Could not find any tables to track.');
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

        $chainManager = new ChainManager();
        $chain = $chainManager->validateChain($trackTables);
        if ($chain !== true) {
            if (!isset($options['force'])) {
                if ($chain == ChainManager::ERROR_CHAIN_TABLE_MISMATCH) {
                    $this->userInteraction->outputMessage(
                        'It has been detected that your are trying to track a different number of tables than you have previously.'
                    );
                } else if ($chain == ChainManager::ERROR_CHAIN_TABLE_CHANGED) {
                    $this->userInteraction->outputMessage(
                        'It has been detected that one or more tables you are trying to track have been updated outside of a tracking session.'
                    );
                }
                $this->userInteraction->outputMessage(
                    'This will result in breaking the tracking chain and you will not be able to revert to a previous state.'
                );
                throw new \Exception('Checksum mismatch - tracking chain will break. Use --force to force tracking.');
            }

            $this->dbManager->ChainBroken();
        }

        // Prepare all required tables.
        $this->dbManager->prepareTrackTables();

        // Create triggers.
        $this->dbManager->createTriggers($trackTables);

        // Save current checksum (for future chain checks).
        $chainManager->save($trackTables);

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
}