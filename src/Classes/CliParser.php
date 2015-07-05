<?php

namespace DBtrack;

class CliParser {

    /** @var \stdClass */
    protected $command = null;

    /**
     * Initialise command line parser.
     * @param array $arguments
     * @throws \Exception
     */
    public function __construct(array $arguments) {
        $this->parseCommandLine($arguments);
    }

    /**
     * Parse passed command line arguments.
     * @param array $arguments
     * @throws \Exception
     */
    protected function parseCommandLine(array $arguments) {
        if (count($arguments) <= 1) {
            $arguments[1] = 'help';
        }

        $command = $this->cleanCommand($arguments[1]);
        $className = "DBtrack\\Commands\\{$command}";
        if (!class_exists($className)) {
            throw new \Exception('Invalid command: ' . $command);
        }

        $this->command = new \stdClass();
        $this->command->name = $command;
        $this->command->options = array_slice($arguments, 2);
    }

    /**
     * Clean command's name. A-Z, a-z, 0-9 and _ - are allowed.
     * @param $value
     * @return mixed
     */
    protected function cleanCommand($value) {
        return preg_replace('/[^A-Za-z0-9_-]/i', '', $value);
    }

    /**
     * Retrieve command
     * @return string
     */
    public function getCommand() {
        return $this->command->name;
    }

    /**
     * Retrieve options.
     * @return array
     */
    public function getOptions() {
        return $this->command->options;
    }
}