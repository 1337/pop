<?php
    class Mediator {
        // Coordinates cross-module communication.
        public static $hooks = array();

        public static function fire($event, $params=null) {
            if (!isset(self::$hooks[$event])) return;

            for ($i = 0; $i < sizeof(self::$hooks[$event]); $i++) {
                try {
                    $params = $params || self::$hooks[$event][$i][1];
                    call_user_func_array(self::$hooks[$event][$i][0], $params);
                } catch (Exception $err) { }
            }
        }

        public static function on($event, $func, $params=null) {
            if (!isset(self::$hooks[$event])) {
                self::$hooks[$event] = array();
            }
            self::$hooks[$event][] = array($func, $params);
        }

        public static function off($event) {
            if (isset(self::$hooks[$event])) {
                unset(self::$hooks[$event]);
            }
        }

        public static function replace($event, $func, $params=null) {
            self::off($event);
            self::on($event, $func, $params);
        }
    }