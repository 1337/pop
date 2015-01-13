<?php

namespace Pop;

const VERSION = '1.0';

/**
 * has something to do with spl_autoload_register.
 *
 * http://php.net/manual/en/function.autoload.php
 * @param $classname
 */
function __autoload($className) {
    // static $loaded_modules = [];
    $paths = [
        PATH,
        PATH . 'lib' . DIRECTORY_SEPARATOR,
        PATH . 'modules' . DIRECTORY_SEPARATOR,
    ];

    $className = str_replace(__NAMESPACE__ . '\\', '', $className);  // remove Pop\
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    foreach ($paths as $path) {
        $fullyQualifiedPath = "$path$className.php";
        // print "$fullyQualifiedPath \n";
        if (!file_exists($fullyQualifiedPath)) {
            continue;
        }
        require_once($fullyQualifiedPath);
    }
}
spl_autoload_register(__NAMESPACE__ . '\__autoload');

//
//
//function _exception_handler($errno, $errstr) {
//    // do nothing?
//    error_log($errstr, 0);
//
//    return true;
//}
//
//
//function debug($msg) {
//    // debug() accepts the same parameters as printf() typically does.
//    $format_string_args = array_slice(func_get_args(), 1);
//
//    // add to the stack
//    Pop::$debug_messages[] = array($msg, $format_string_args);
//}
//
//
//class Pop {
//// variables
//    private static $all_hooks = [];
//    public static $models_cache = [];
//    public static $debug_messages = [];
//
//// magics
//    public function __construct() {
//        global $modules;
//
//        // whenever you call "new Class()", __autoload will be called!
//        spl_autoload_register(__NAMESPACE__ . '\__autoload');
//        // force Model (required)
//        $model = new Model();
//        unset ($model);
//
//        // '... zlib.output_compression is preferred over ob_gzhandler().'
//        if (!ob_get_level() && //
//            isset ($_SERVER['HTTP_ACCEPT_ENCODING']) &&
//            strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') >= 0
//        ) {
//            // compress output if client likes that
//            @ini_set('zlib.output_compression', 4096);
//        }
//        @ob_start(); // prevent "failed to delete buffer" errors
//
//        if (USE_POP_REDIRECTION === true) { // start rendering
//            self::_load_handlers(); // $all_hooks
//            try { // load responsible controller
//                $url_parts = parse_url($_SERVER['REQUEST_URI']);
//                list($mod, $handler) = self::url($url_parts['path'], 1);
//                $page = Pop::obj($mod, null);
//                $page->$handler(); // load only one page...
//                die();
//            } catch (\Exception $err) {
//                // core error handler (not that it always works)
//                debug($err->getMessage());
//            }
//        } else { // else: use POP as library
//            // register_shutdown_function(array(__NAMESPACE__ . '\View', 'render'));
//        }
//
//        // CodeIgniter technique
//        set_error_handler(__NAMESPACE__ . '\_exception_handler');
//        if (!self::phpver(5.3)) {
//            @set_magic_quotes_runtime(0); // Kill magic quotes
//        }
//    }
//
//    public static function obj() {
//        // real signature: obj(className, *args)
//        // returns a Pop instance of that class name.
//        $args = func_get_args();
//        $className = $args[0];
//        if (!isset ($args[1])) {
//            $args[1] = null; // add default [1] if missing
//        }
//
//        return new $className ($args[1]);
//    }
//
//    public static function phpver($checkver = null) {
//        // checkver? --> bool
//        // no checkver? --> float
//        $current_version = str_replace('.', '', phpversion()) / 100;
//        if ($checkver) {
//            $check_version = str_replace('.', '', $checkver) / 100;
//
//            return ($current_version >= $check_version);
//        }
//
//        return $current_version;
//    }
//
//    public static function url($url = '', $verbose = false) {
//        // provide the name of the handler that serves a given url.
//        if ($url === '') {
//            $url = $_SERVER['REQUEST_URI'];
//        }
//
//        foreach ((array)self::$all_hooks as $module => $hooks) {
//            foreach ((array)$hooks as $hook => $handler) {
//                // On malformed URLs, parse_url() may return FALSE
//                $url_parts = parse_url($url);
//                if ($url_parts) {
//                    $match = preg_match(
//                        '#^/' . SUBDIR . '?' . $hook . '$#i',
//                        $url_parts['path']
//                    );
//                    if ($match) { // 1 = match
//                        return array($module, $handler);
//                    }
//                }
//            }
//        }
//
//        if ($verbose) {
//            throw new \Exception('403 Forbidden ' . $url);
//        } else {
//            return false;
//        }
//    }
//
//// private functions
//    private static function _load_handlers() {
//        // because Spyc is slow, we cache handler-URL maps
//        global $modules;
//        $url_cache = CACHE_PATH . '_url_cache.json';
//
//        // filemtime will fail if file does not exist!
//        if (file_exists($url_cache) &&
//            (time() - filemtime($url_cache)) < 3600
//        ) {
//            try { // because
//                self::$all_hooks = json_decode(file_get_contents($url_cache),
//                                               true);
//            } catch (\Exception $err) {
//                debug('URL cache is corrupted: %s',
//                            $err->getMessage());
//            }
//        } else { // load URLs from all handlers... and cache them.
//            require_once(LIBRARY_PATH . 'spyc.php');
//            foreach ($modules as $idx => $module) {
//                $yaml_path = MODULE_PATH . $module . '.yaml';
//                try {
//                    if (file_exists($yaml_path)) {
//                        $yaml = Spyc::YAMLLoad($yaml_path);
//                        $handlers = (array)$yaml['handlers'];
//                        foreach ($handlers as $i => $handler) {
//                            // make loop to break handler keys from values.
//                            foreach ($handler as $hk => $hndl) {
//                                self::$all_hooks[$module][$hk] = $hndl;
//                            }
//                        }
//                    }
//                } catch (\Exception $err) {
//                    debug($err);
//                }
//            }
//            @file_put_contents($url_cache, json_encode(self::$all_hooks));
//        }
//    }
//}
//
//return new Pop();
