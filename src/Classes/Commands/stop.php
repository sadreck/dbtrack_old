<?php

namespace DBtrack\Commands;

use DBtrack\Base\AppHandler;
use DBtrack\Base\Command;

class stop extends Command {
    public function execute() {
        $this->prepare();

        $options = $this->parseOptions();
        $message = '';
        if (isset($options['message'], $options['message'][0])) {
            $message = $options['message'][0];
        }

        $this->dbManager->clearTriggers();

        $groupId = $this->getGroupID();
        $actionCount = $this->dbManager->commit($groupId, $message);

        $this->userInteraction->outputMessage('Tracking stopped. '. $actionCount .' action(s) tracked.');
        return true;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'stop' . "\t\t" . 'Stop tracking.');
        } else {
            $this->userInteraction->outputMessage('stop' . "\t\t" . 'Stops tracking.');
        }
    }
}