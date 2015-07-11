<?php

namespace DBtrack\Base;

abstract class Command extends DBtrack {
    /** @var array */
    protected $options = array();

    /** @var UserInteraction */
    protected $userInteraction = null;

    /** @var DBManager */
    protected $dbManager = null;

    /**
     * Executes the specified command.
     * @return mixed
     */
    abstract public function execute();

    /**
     * Displays help.
     * @param array $options
     * @return mixed
     */
    abstract public function help(array $options);

    /**
     * Initialise command.
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options) {
        $this->options = $options;
        $this->userInteraction = AppHandler::getObject('UserInteraction');

        $this->loadEventListeners();

        parent::__construct();
    }

    /**
     * Load event listeners if there are any.
     */
    protected function loadEventListeners() {
        // Load event handlers if there are any.
        if (!method_exists($this, 'eventHandlers')) {
            return false;
        }

        $events = $this->eventHandlers();
        if (empty($events)) {
            return false;
        }

        foreach ($events as $event) {
            if (!Events::addEventListener($event)) {
                throw new \Exception('Could not add event listener for: ' . print_r($events, true));
            }
        }
    }

    /**
     * Check, load and prepare config.
     * @return bool
     * @throws \Exception
     */
    protected function prepare() {
        if (!$this->isInitialised()) {
            throw new \Exception('dbtrack has not been initialised. Run <dbt init> first.');
        }

        $config = $this->loadConfig();
        if ($config === false) {
            throw new \Exception('Could not load config. Try initialising dbtrack.');
        }

        if (!$this->initDatabase($config->datatype, $config->hostname, $config->database, $config->username, $config->password)) {
            throw new \Exception('Could not connect to database');
        }

        $this->dbManager = AppHandler::setObject('DBManager', new DBManager($this->dbms));

        return true;
    }

    protected function parseOptions() {
        $options = $this->options;
        $arguments = array();

        if (count($options) == 0) {
            return $arguments;
        }

        $key = '';
        do {
            $option = array_shift($options);

            if (substr($option, 0, 2) == '--') {
                $key = strtolower(substr($option, 2));
                $arguments[$key] = array();
            } else if (!empty($key)) {
                $arguments[$key][] = $option;
            } else {
                $arguments[] = $option;
            }
        } while (count($options) > 0);

        return $arguments;
    }
}