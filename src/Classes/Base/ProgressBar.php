<?php

namespace DBtrack\Base;

class ProgressBar {
    /** @var UserInteraction */
    protected $display = null;

    /** @var string */
    protected $progressCharacter = '';

    /** @var int */
    protected $updateFrequency = 0;

    public function __construct($updateFreq = 0, $progCharacter = '=') {
        $this->progressCharacter = $progCharacter;
        $this->updateFrequency = $updateFreq;
        $this->display = AppHandler::getObject('UserInteraction');
    }

    /**
     * Get number of columns.
     * @return string
     */
    protected function getColumns() {
        return (int)exec("tput cols");
    }

    /**
     * @param $current
     * @param $total
     * @return bool
     */
    public function update($current, $total) {
        // Avoid division by zero and percentage > 100%.
        if ($total == 0 || $current > $total) {
            return false;
        } else if ($this->updateFrequency > 0 && $current != $total && ($current % $this->updateFrequency != 0)) {
            return false;
        }

        $columns = $this->getColumns();

        if ($current == $total) {
            // Move a line up.
            $this->display->outputMessage("\x1b[A", false);
            // Clean line.
            $this->display->outputMessage(str_repeat(' ', $columns));
            // Move a line up.
            $this->display->outputMessage("\x1b[A", false);
            return false;
        }

        $width = $columns - 2;
        $percent = (float)($current / $total);
        $bar = floor($percent * $width);

        // Move cursor to the beginning of the row.
        $this->display->outputMessage("\r", false);

        $output = '[';
        $output .= str_repeat($this->progressCharacter, $bar);
        if ($bar < $width) {
            $output .= '>';
            $output .= str_repeat(' ', $width - $bar - 1);
        }
        $output .= ']';

        $this->display->outputMessage($output, false);

        if ($current == $total) {
            $this->display->outputMessage(''); // New line.
        }
        return true;
    }
}