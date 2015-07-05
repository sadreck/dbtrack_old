<?php

namespace DBtrack;

class Manager {

    /** @var CliParser */
    protected $cli = null;

    /**
     * Enable DBtrack manager.
     * @param array $arguments
     */
    public function __construct(array $arguments) {
        $this->cli = new CliParser($arguments);
    }

    public function run() {
        $command = $this->cli->getCommand();
        $className = "DBtrack\\Commands\\{$command}";
        if (!class_exists($className)) {
            throw new \Exception('Invalid command: ' . $command);
        }
        $command = new $className($this->cli->getOptions());
        $command->execute();
    }
}