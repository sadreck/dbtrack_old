<?php

namespace DBtrack\Base;

class AppHandler {
    private static $_appObjects = array();

    public static function getObject($name) {
        if (!isset(self::$_appObjects[$name])) {
            throw new \Exception('Application handler not set for: ' . $name);
        }
        return self::$_appObjects[$name];
    }

    public static function setObject($name, $object) {
        self::$_appObjects[$name] = $object;
        return self::$_appObjects[$name];
    }
}