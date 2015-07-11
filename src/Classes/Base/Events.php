<?php

namespace DBtrack\Base;

class Events {
    private static $_listeners = array();

    /**
     * Trigger a custom event.
     * @param \stdClass $data Must have ->event and ->params properties.
     * @return bool
     */
    public static function triggerEvent(\stdClass $data) {
        if (!isset($data->event, $data->params)) {
            return false;
        } else if (!isset(self::$_listeners[$data->event])) {
            return false;
        }

        foreach (self::$_listeners[$data->event] as $listener) {
            if (is_callable($listener->function)) {
                try {
                    call_user_func($listener->function, $data->params);
                } catch (\Exception $e) {
                    // TODO: Log failed trigger.
                }
            }
        }
    }

    /**
     * Add a custom event listener.
     * @param \stdClass $listener Must have ->event and ->function properties.
     * @return bool
     */
    public static function addEventListener(\stdClass $listener) {
        if (!isset($listener->event, $listener->function)) {
            return false;
        }

        if (!isset(self::$_listeners[$listener->event])) {
            self::$_listeners[$listener->event] = array();
        }

        $event = new \stdClass();
        $event->function = $listener->function;

        self::$_listeners[$listener->event][] = $event;
        return true;
    }
}