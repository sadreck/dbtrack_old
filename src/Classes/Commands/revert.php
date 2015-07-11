<?php

namespace DBtrack\Commands;

use DBtrack\Base\ActionParser;
use DBtrack\Base\Command;
use DBtrack\Commands\Helpers\Revert\Helper;

class revert extends Command {

    /** @var Revert/Helper */
    protected $helper = null;

    public function execute() {
        $this->prepare();

        if ($this->dbManager->hasTriggers()) {
            throw new \Exception('dbtrack is still running.');
        } else if (!$this->dbManager->trackingTablesExist()) {
            throw new \Exception('Tracking tables do not exist. Use <dbt start> to start.');
        }

        $actionParser = new ActionParser();
        $this->helper = new Helper();

        $options = $this->parseOptions($this->options);
        if (!isset($options[0]) || empty($options[0])) {
            throw new \Exception('No dbtrack group id specified. Use <dbt stats> to get one.');
        }

        if (!$actionParser->groupExists($options[0])) {
            throw new \Exception('Group ID does not exist: ' . $options[0]);
        }

        $allGroups = $actionParser->getAllGroups($options[0]);

        $revertedActions = 0;
        $this->dbms->beginTransaction();
        try {
            foreach ($allGroups as $group) {
                $actions = $actionParser->parseGroup($group->id);
                $actions = array_reverse($actions);
                $revertedActions += count($actions);

                if ($this->helper->revert($actions)) {
                    $sql = "DELETE FROM dbtrack_data
                        WHERE actionid IN (SELECT id FROM dbtrack_actions WHERE groupid = :groupid)";
                    $this->dbms->executeQuery($sql, array('groupid' => $group->id));

                    $sql = "DELETE FROM dbtrack_actions WHERE groupid = :groupid";
                    $this->dbms->executeQuery($sql, array('groupid' => $group->id));
                }
            }
            $this->dbms->commitTransaction();
        } catch (\Exception $e) {
            $this->dbms->rollbackTransaction();
            throw new \Exception($e->getMessage(), 0, $e);
        }

        $this->userInteraction->outputMessage($revertedActions . ' action(s) have been reverted.');
        return true;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'revert' . "\t\t" . 'Revert database history to a specified tracked point.');
        } else {
            $this->userInteraction->outputMessage('reset' . "\t\t" . 'Revert database history to a specified tracked point.');
            $this->userInteraction->outputMessage('');
            $this->userInteraction->outputMessage('Usage:' . "\t\t" . 'dbt revert <groupid>');
            $this->userInteraction->outputMessage('<groupid> is the group id from the <dbt stats> command.');
        }
    }
}