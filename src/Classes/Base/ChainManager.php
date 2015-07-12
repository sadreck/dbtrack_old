<?php

namespace DBtrack\Base;

class ChainManager extends DBtrack {
    /** @var DBManager */
    protected $dbManager = null;

    const ERROR_CHAIN_TABLE_MISMATCH = 1;
    const ERROR_CHAIN_TABLE_CHANGED = 2;

    public function __construct() {
        parent::__construct();
        $this->dbManager = AppHandler::getObject('DBManager');
        $this->dbms = AppHandler::getObject('Database');
    }

    public function validateChain(array $tables) {
        $currentChecksums = $this->dbManager->getChecksums($tables);
        $previousChecksums = $this->loadStoredChecksums();

        if (count($previousChecksums) > 0 && count($previousChecksums) != count($currentChecksums)) {
            // Chain will be broken because the previous and current checksums don't match.
            return self::ERROR_CHAIN_TABLE_MISMATCH;
        } else if (!$this->compareChecksums($previousChecksums, $currentChecksums)) {
            // Chain will be broken because some tables had been updated out of the tracking session.
            return self::ERROR_CHAIN_TABLE_CHANGED;
        }

        return true;
    }

    /**
     * Save checksums for future chain validation.
     * @param array $tables
     * @return bool
     */
    public function save(array $tables) {
        $checksums = $this->dbManager->getChecksums($tables);
        return $this->saveChecksums($checksums);
    }

    /**
     * Compare previous checksums with current ones.
     * @param array $previous
     * @param array $current
     * @return bool
     */
    protected function compareChecksums(array $previous, array $current) {
        foreach ($previous as $table => $checksum) {
            if (!isset($current[$table])) {
                // Table does not exist.
                return false;
            } else if ($current[$table] != $checksum) {
                // Table checksums don't match.
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a chain is broken.
     * @param $groupId
     * @return bool
     */
    public function isBrokenChain($groupId) {
        $result = $this->dbms->getResult(
            "SELECT id FROM dbtrack_actions WHERE groupid = :groupid AND brokenchain = 1",
            array('groupid' => $groupId)
        );
        return !empty($result);
    }
}