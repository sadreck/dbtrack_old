<?php

namespace DBtrack\Base;

class ColourTerminal {

    /**
     * Define available foreground colours.
     * @var array
     */
    protected $foregroundColours = array(
        'black' => '0;30',
        'blue' => '0;34',
        'green' => '0;32',
        'cyan' => '0;36',
        'red' => '0;31',
        'yellow' => '1;33',
        'white' => '1;37',
        'light_blue' => '1;34',
        'light_green' => '1;32',
        'light_cyan' => '1;36',
        'light_red' => '1;31',
        'light_gray' => '0;37',
    );

    /**
     * Define available background colours.
     * @var array
     */
    protected $backgroundColours = array(
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47'
    );

    /**
     * Return coloured text.
     * @param $text
     * @param $foreColour
     * @param string $backColour
     * @return string
     */
    public function getColourText($text, $foreColour, $backColour = '') {
        if (empty($foreColour) && empty($backColour)) {
            return $text;
        }

        if (!empty($foreColour) && isset($this->foregroundColours[$foreColour])) {
            $foreColour = "\033[" . $this->foregroundColours[$foreColour] . "m";
        } else {
            $foreColour = '';
        }

        if (!empty($backColour) && isset($this->backgroundColours[$backColour])) {
            $backColour = "\033[" . $this->backgroundColours[$backColour] . "m";
        } else {
            $backColour = '';
        }

        return $foreColour . $backColour . $text . "\033[0m";
    }
}