<?php

namespace DBtrack\Commands;

use DBtrack\Base\Command;
use DBtrack\Commands\Helpers\Stats\Helper;

class stats extends Command {
    /** @var Helper */
    protected $helper = null;

    public function execute() {
        $this->prepare();

        $this->helper = new Helper();

        $this->helper->showStatsList();
        return true;
    }

    public function help(array $options) {
        if (count($options) == 0) {
            $this->userInteraction->outputMessage("\t" . 'stats' . "\t\t" . 'Displays all tracking history (grouped).');
        } else {
            $this->userInteraction->outputMessage('stats' . "\t\t" . 'Displays all tracking history (grouped).');
        }
    }
}