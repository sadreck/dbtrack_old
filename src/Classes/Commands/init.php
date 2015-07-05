<?php

namespace DBtrack\Commands;

use DBtrack\Base\Command;

class init extends Command {
    public function execute() {
        if ($this->isInitialised()) {
            if (!$this->confirmReInitialise()) {
                return false;
            }
        }

        // Try to create the dbtrack directory.
        if (!is_dir($this->dbtDirectory)) {
            if (!@mkdir($this->dbtDirectory)) {
                throw new \Exception('Could not create directory: ' . $this->dbtDirectory);
            }
        }

        $data = $this->getInitData();

        if (!$this->initDatabase($data->datatype, $data->hostname, $data->database, $data->username, $data->password)) {
            throw new \Exception('Error: Could not connect to database.');
        }

        if (!$this->saveConfig($data)) {
            throw new \Exception('Error: Could not save config file.');
        }

        $this->userInteraction->outputMessage('dbtrack has been initialised.');
        return true;
    }

    /**
     * Confirm if user wants to re-initialise dbtrack within the current directory.
     * @return bool
     */
    protected function confirmReInitialise() {
        $this->userInteraction->outputMessage('This directory has already been initialised. Are you sure you want to re-initialise?');
        $answer = $this->userInteraction->promptUser('Y/N: ');
        return (trim(strtolower($answer)) == 'y');
    }

    /**
     * Get required initialisation data.
     * @return \stdClass
     * @throws \Exception
     */
    protected function getInitData() {
        $databases = $this->getDatabases();
        if (count($databases) == 0) {
            throw new \Exception('No database controllers detected.');
        }

        $datatype = $this->userInteraction->promptUser('DBMS ('. implode('/', $databases) .'): ');
        $hostname = $this->userInteraction->promptUser('Database hostname: ');
        $database = $this->userInteraction->promptUser('Database name: ');
        $username = $this->userInteraction->promptUser('Database username: ');
        $password = $this->userInteraction->promptUser('Database password: ');

        if (empty($datatype) || empty($hostname) || empty($database) || empty($username) || empty($password)) {
            throw new \Exception('You must enter all credentials');
        }

        $data = new \stdClass();
        $data->datatype = $datatype;
        $data->hostname = $hostname;
        $data->database = $database;
        $data->username = $username;
        $data->password = $password;

        return $data;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'init' . "\t\t" . 'Initialise a directory with dbtrack.');
        } else {
            $this->userInteraction->outputMessage('Initialise a directory with dbtrack.');
        }
    }
}