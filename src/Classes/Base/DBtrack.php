<?php

namespace DBtrack\Base;

class DBtrack {
    /** @var string The directory where dbtrack has been executed from. */
    protected $baseDirectory = '';

    /** @var string The directory where all config information will be stored. */
    protected $dbtDirectory = '';

    /** @var Database */
    protected $dbms = null;

    const VERSION = '0.1';

    /**
     * Initialise main class.
     * @throws \Exception
     */
    public function __construct() {
        $this->baseDirectory = getcwd();
        $this->dbtDirectory = $this->baseDirectory . '/.dbtrack';

        if (is_dir($this->dbtDirectory) && !is_writable($this->dbtDirectory)) {
            throw new \Exception('Permission denied to read/write to: ' . $this->dbtDirectory);
        }
    }

    /**
     * Check if DBtrack has already been initialised.
     * @return bool
     */
    protected function isInitialised() {
        return is_dir($this->dbtDirectory) && file_exists($this->dbtDirectory . '/config');
    }

    /**
     * Get list of supported databases.
     * @return array
     */
    protected function getDatabases() {
        $list = array();
        $files = glob(ROOT . '/Classes/Databases/*.php');

        foreach ($files as $file) {
            $info = pathinfo($file);
            $list[] = $info['filename'];
        }

        return $list;
    }

    /**
     * Initialise a database connection.
     * @param $type
     * @param $hostname
     * @param $database
     * @param $username
     * @param $password
     * @return bool
     * @throws \Exception
     */
    protected function initDatabase($type, $hostname, $database, $username, $password) {
        $className = "DBtrack\\Databases\\{$type}";
        if (!class_exists($className)) {
            throw new \Exception('Unsupported database type: ' . $type);
        }

        $this->dbms = AppHandler::setObject('Database', new $className($hostname, $database, $username, $password));

        return $this->dbms->connect();
    }

    /**
     * Save config.
     * @param \stdClass $config
     * @return bool
     */
    protected function saveConfig(\stdClass $config) {
        @unlink($this->dbtDirectory . '/config');
        if (file_exists($this->dbtDirectory . '/config')) {
            // Could not delete config file.
            return false;
        }

        file_put_contents($this->dbtDirectory . '/config', json_encode($config));
        if (!file_exists($this->dbtDirectory . '/config')) {
            // Could not create config file.
            return false;
        }

        return true;
    }

    /**
     * Load config.
     * @return bool|\stdClass
     */
    protected function loadConfig() {
        if (!$this->isInitialised()) {
            return false;
        }

        $data = file_get_contents($this->dbtDirectory . '/config');
        if (empty($data)) {
            return false;
        }

        $config = json_decode($data);
        if (!$this->validateConfig($config)) {
            return false;
        }

        return $config;
    }

    /**
     * Validate config.
     * @param $config
     * @return bool
     */
    protected function validateConfig($config) {
        if (!isset($config->datatype) || !isset($config->hostname) ||
            !isset($config->database) || !isset($config->username) ||
            !isset($config->password)) {

            return false;
        }
        return true;
    }

    /**
     * Get the next available groupid.
     * @return string
     */
    protected function getGroupID() {
        $sql = "SELECT COALESCE(MAX(groupid), 0) AS groupid FROM dbtrack_actions";
        $result = $this->dbms->getResult($sql);
        return ++$result->groupid;
    }
}