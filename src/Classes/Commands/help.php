<?php

namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Base\DBtrack;

class help extends Command {
    public function execute() {
        $commands = $this->getCommands();
        sort($commands);

        $this->userInteraction->outputMessage('dbtrack v' . DBtrack::VERSION . "\t\t\t\t\tPavel Tsakalidis [p@vel.gr]");
        $this->userInteraction->outputMessage('');

        $options = $this->parseOptions();
        if (count($options) == 0) {
            $this->userInteraction->outputMessage('Available commands:');

            foreach ($commands as $command) {
                $instance = $this->getCommandObject($command);
                if ($instance === false) {
                    continue;
                }
                $instance->help(array());
            }

            $this->userInteraction->outputMessage('');
            $this->userInteraction->outputMessage('For more options type: dbt <command> more');
        } else {
            $instance = $this->getCommandObject($options[0]);
            if ($instance === false) {
                $this->userInteraction->outputMessage('Command not found');
            } else {
                $instance->help($options);
            }
        }
    }

    /**
     * Get command object instance.
     * @param $command
     * @return bool
     */
    protected function getCommandObject($command) {
        $className = "DBtrack\\Commands\\{$command}";
        if (!class_exists($className)) {
            return false;
        }
        $command = new $className(array());

        return $command;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'help' . "\t\t" . 'Displays this help page.');
        } else {

        }
    }

    /**
     * Return all commands.
     * @return array
     */
    protected function getCommands() {
        $commands = array();
        $files = glob(ROOT . '/Classes/Commands/*.php');
        foreach ($files as $file) {
            $info = pathinfo($file);
            $commands[] = $info['filename'];
        }
        return $commands;
    }
}