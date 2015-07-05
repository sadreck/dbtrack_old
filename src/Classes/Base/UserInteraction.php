<?php

namespace DBtrack\Base;

class UserInteraction extends ColourTerminal {
    /** @var array */
    protected $header = array();

    /** @var array */
    protected $padding = array();

    /**
     * Set header.
     * @param array $header
     */
    public function setHeader(array $header) {
        $this->header = $header;
    }

    /**
     * Set padding.
     * @param array $padding
     */
    public function setPadding(array $padding) {
        $this->padding = $padding;
    }

    /**
     * Print header.
     * @throws \Exception
     */
    public function showHeader() {
        $this->showLine($this->header);
    }

    /**
     * Print line.
     * @param array $line
     * @return bool
     * @throws \Exception
     */
    public function showLine(array $line = array()) {
        if (count($line) == 0) {
            $this->outputMessage('');
            return true;
        }

        if (count($line) != count($this->padding)) {
            throw new \Exception('showLine() array count mismatch');
        }

        $string = '| ';
        for ($i = 0; $i < count($line); $i++) {
            $string .= str_pad($line[$i], $this->padding[$i], ' ', STR_PAD_RIGHT) . ' | ';
        }
        $this->outputMessage($string);
        return true;
    }

    /**
     * Get user input from command line.
     * @param $message
     * @return string
     */
    public function promptUser($message) {
        echo $message;

        $handle = fopen('php://stdin', 'r');
        $input = trim(fgets($handle));
        fclose($handle);

        return $input;
    }

    /**
     * Display message to user.
     * @param $message
     * @param bool $newLine
     */
    public function outputMessage($message, $newLine = true) {
        echo $message;
        if ($newLine) {
            echo PHP_EOL;
        }
    }
}