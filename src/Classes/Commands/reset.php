<?php

namespace DBtrack\Commands;

use DBtrack\Base\Command;

class reset extends Command {
    public function execute() {
        $this->prepare();

        if ($this->dbManager->hasTriggers()) {
            throw new \Exception('dbtrack is still running.');
        }

        if (!in_array('--y', $this->options)) {
            if ($this->userInteraction->promptUser('Are you sure you want to drop all tracking tables? (Y/N): ') != 'y') {
                return true;
            }
        }

        // Clear existing triggers;
        $this->dbManager->clearTriggers();

        // Drop table.
        $this->dbManager->deleteTrackTables();

        // Clean chain file.
        $this->clearChecksums();

        $this->userInteraction->outputMessage('dbtrack - all triggers and tables have been dropped.');
        return true;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'reset' . "\t\t" . 'Reset dbtrack. Drops all dbtrack tables and triggers/functions.');
        } else {
            $this->userInteraction->outputMessage('reset' . "\t\t" . 'Reset dbtrack tracking tables and triggers/functions.');
            $this->userInteraction->outputMessage('');
            $this->userInteraction->outputMessage('Available arguments:');
            $this->userInteraction->outputMessage("\t" . '--y' . "\t" . 'Skip confirmation prompt and reset dbtrack immediately.');
        }
    }
}