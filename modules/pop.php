<?php

namespace Pop;

/**
 * has something to do with spl_autoload_register.
 *
 * http://php.net/manual/en/function.autoload.php
 * @param $classname
 */
function __autoload($class_name) {
    static $loaded_modules = array();
    $paths = array(PATH, MODULE_PATH, LIBRARY_PATH);
    $class_name = str_replace(__NAMESPACE__ . '\\', '', $class_name);  // remove Pop\
    foreach ($paths as $path) {
        $fqp = "$path$class_name.php";
        // print "$fqp \n";
        if (file_exists($fqp)) {
            include_once $fqp;
            $loaded_modules[] = $class_name;
            break;
        }
    }
}


function _exception_handler($errno, $errstr) {
    // do nothing?
    error_log($errstr, 0);

    return true;
}


class Pop {
// variables
    private static $all_hooks = array();
    public static $models_cache = array();
    public static $debug_messages = array();

// magics
    public function __construct() {
        global $modules;

        // whenever you call "new Class()", __autoload will be called!
        spl_autoload_register(__NAMESPACE__ . '\__autoload');
        // force Model (required)
        $model = new Model();
        unset ($model);

        // '... zlib.output_compression is preferred over ob_gzhandler().'
        if (!ob_get_level() && //
            isset ($_SERVER['HTTP_ACCEPT_ENCODING']) &&
            strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') >= 0
        ) {
            // compress output if client likes that
            @ini_set('zlib.output_compression', 4096);
        }
        @ob_start(); // prevent "failed to delete buffer" errors

        if (USE_POP_REDIRECTION === true) { // start rendering
            self::_load_handlers(); // $all_hooks
            try { // load responsible controller
                $url_parts = parse_url($_SERVER['REQUEST_URI']);
                list($mod, $handler) = self::url($url_parts['path'], 1);
                $page = Pop::obj($mod, null);
                $page->$handler(); // load only one page...
                die();
            } catch (\Exception $err) {
                // core error handler (not that it always works)
                self::debug($err->getMessage());
            }
        } else { // else: use POP as library
            register_shutdown_function(array(__NAMESPACE__ . '\View', 'render'));
        }

        // CodeIgniter technique
        set_error_handler(__NAMESPACE__ . '\_exception_handler');
        if (!self::phpver(5.3)) {
            @set_magic_quotes_runtime(0); // Kill magic quotes
        }
    }

// public functions
    public static function debug($msg) {
        // debug() accepts the same parameters as printf() typically does.
        $format_string_args = array_slice(func_get_args(), 1);

        // add to the stack
        self::$debug_messages[] = array($msg, $format_string_args);
    }

    public static function obj() {
        // real signature: obj(class_name, *args)
        // returns a Pop instance of that class name.
        $args = func_get_args();
        $class_name = $args[0];
        if (!isset ($args[1])) {
            $args[1] = null; // add default [1] if missing
        }

        return new $class_name ($args[1]);
    }

    public static function phpver($checkver = null) {
        // checkver? --> bool
        // no checkver? --> float
        $current_version = str_replace('.', '', phpversion()) / 100;
        if ($checkver) {
            $check_version = str_replace('.', '', $checkver) / 100;

            return ($current_version >= $check_version);
        }

        return $current_version;
    }

    public static function url($url = '', $verbose = false) {
        // provide the name of the handler that serves a given url.
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }

        foreach ((array)self::$all_hooks as $module => $hooks) {
            foreach ((array)$hooks as $hook => $handler) {
                // On malformed URLs, parse_url() may return FALSE
                $url_parts = parse_url($url);
                if ($url_parts) {
                    $match = preg_match(
                        '#^/' . SUBDIR . '?' . $hook . '$#i',
                        $url_parts['path']
                    );
                    if ($match) { // 1 = match
                        return array($module, $handler);
                    }
                }
            }
        }

        if ($verbose) {
            throw new \Exception('403 Forbidden ' . $url);
        } else {
            return false;
        }
    }

// private functions
    private static function _load_handlers() {
        // because Spyc is slow, we cache handler-URL maps
        global $modules;
        $url_cache = CACHE_PATH . '_url_cache.json';

        // filemtime will fail if file does not exist!
        if (file_exists($url_cache) &&
            (time() - filemtime($url_cache)) < 3600
        ) {
            try { // because
                self::$all_hooks = json_decode(file_get_contents($url_cache),
                                               true);
            } catch (\Exception $err) {
                self::debug('URL cache is corrupted: %s',
                            $err->getMessage());
            }
        } else { // load URLs from all handlers... and cache them.
            require_once(LIBRARY_PATH . 'spyc.php');
            foreach ($modules as $idx => $module) {
                $yaml_path = MODULE_PATH . $module . '.yaml';
                try {
                    if (file_exists($yaml_path)) {
                        $yaml = Spyc::YAMLLoad($yaml_path);
                        $handlers = (array)$yaml['Handlers'];
                        foreach ($handlers as $i => $handler) {
                            // make loop to break handler keys from values.
                            foreach ($handler as $hk => $hndl) {
                                self::$all_hooks[$module][$hk] = $hndl;
                            }
                        }
                    }
                } catch (\Exception $err) {
                    self::debug($err);
                }
            }
            @file_put_contents($url_cache, json_encode(self::$all_hooks));
        }
    }
}

return new Pop();