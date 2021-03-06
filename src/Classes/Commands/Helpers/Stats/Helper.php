<?php

namespace DBtrack\Commands\Helpers\Stats;

use DBtrack\Base\Database;
use DBtrack\Base\DBManager;
use DBtrack\Base\UserInteraction;

class Helper {
    /** @var Database */
    protected $dbms = null;

    /** @var DBManager */
    protected $dbManager = null;

    /** @var UserInteraction */
    protected $display = null;

    /**
     * Initialise helper.
     * @param Database $dbms
     * @param DBManager $dbManager
     */
    public function __construct(Database $dbms, DBManager $dbManager) {
        $this->dbms = $dbms;
        $this->dbManager = $dbManager;
        $this->display = new UserInteraction();
    }

    /**
     * Display stats.
     * @return bool
     */
    public function showStatsList() {
        $sql = "SELECT groupid, MIN(timeadded) AS mintime, MAX(timeadded) AS maxtime, COUNT(id) AS numactions
                FROM dbtrack_actions
                GROUP BY groupid
                ORDER BY MIN(timeadded)";
        $results = $this->dbms->getResults($sql);
        if (empty($results)) {
            $this->display->outputMessage('No stats found.');
            return true;
        }

        $this->display->setHeader(array('Group', 'From', 'To', 'Actions'));
        $this->display->setPadding(array(5, 19, 19, 10));

        $this->display->showHeader();

        foreach ($results as $result) {
            $line = array(
                $result->groupid,
                date('d/m/Y H:i:s', $result->mintime),
                date('d/m/Y H:i:s', $result->maxtime),
                $result->numactions,
            );
            $this->display->showLine($line);
        }
        return true;
    }
}