<?php
define('ROOT', __DIR__);

require_once(ROOT . '/Autoload.php');

use DBtrack\Manager;

try {
    $manager = new Manager($argv);
    $manager->run();
} catch (Exception $e) {
    fputs(STDOUT, $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
}