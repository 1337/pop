<?php
    define ('PATH', dirname (__FILE__) . '/');
    if (file_exists (PATH . 'vars.php')) {
        // put your setup variables in this file
        require_once (PATH . 'vars.php');
    } else {
        // defaults kick in only if you did not set up properly
        require_once (PATH . 'vars.default.php');
    }
    require_once (PATH . 'lib.php');
    
    class Pop {
        /*  http://net-beta.net/ubench/
            TODO: access levels, perm checks, relationships
            TODO: loose coupling (allow modules to only notify the core to induce custom-named events)
            TODO: query indices
            TODO: non-random GUID hash object storage
            TODO: http://stackoverflow.com/questions/3849415
            TODO: declare all vars; undeclared ones 10 times slower
            check for function calls in for loops
            remove @s (reportedly slow) -> harmless errors should be hidden with 0 or E_NONE
            TODO: minimize magics
            TODO: check full paths for includes
            TODO: static functions are 4 times faster
            TODO: switch to singleton (faster / saves memory)
            [v]p[s]rintf is 10x faster than echo("$ $ $"); echo (1,2,3) is also faster
            TODO: add unset()s
            use $_SERVER['REQUEST_TIME'] instead of microtime for start time
            change switch to else if (faster)
            TODO: move templating to client-side
            ++$i is faster than $ i++
            TODO: Use ip2long() and long2ip() to store IP addresses as integers instead of strings
            TODO: avoid global variable changes; cache using local-scope vars first
            TODO: isset($foo[5]) is faster than strlen($foo) > 5
            int list keys are always faster than str list keys
            avoiding classes speeds things up
            array_push is slower than array[] =
            strpos is faster than strstr
            str{5} is 2x faster than substr(str,5,1)
            @ is faster than error_reporting(0)
            isset() is 5x faster than @
            file_get_contents is faster than file
            for code unlikely to throw exceptions, it's faster to use exception trapping.
            for code likely to throw exceptions, it's faster to check your values rather than raising exceptions.
            TODO: === null is 2x faster than is_null
            + is 2x faster than array_merge
            if is faster than shorthand
            nested if is logically faster than &&
            $a = 'func'; $a() is faster than call_user_func, but slower than just calling the function
            avoid $_GLOBALS at all costs; avoid global a,b,c too (2x slower than local vars)
            never use while(next())
            foreach is faster with key
            foreach as &var is 3x faster if loop involves writing to var.
            recursion is 3x slower than not
            it is faster to strtolower+strpos than to stripos.
            (int) is faster than intval
            TODO: array() is marginally faster than (array)
            === is up to 12 times faster than == in all comparisons
        */
        private static $loaded_modules;
        
        public function __construct () {
            spl_autoload_register (array ($this, 'load'));
            // force Model (required)
            $model = new Model (); unset ($model);
        }

        private function load ($name) {
            $filenames = array (
                PATH . $name . '.php',
                MODULE_PATH . $name . '.php',
                LIBRARY_PATH . $name . '.php'
            );
            foreach ($filenames as $idx => $filename) {
                if (file_exists ($filename)) {
                    include_once $filename;
                    self::$loaded_modules[] = $name;
                    break;
                }
            }
        }
    }
    $pop = new Pop (); // register autoloaders
    
    if (USE_POP_REDIRECTION === true) {
        // '... zlib.output_compression is preferred over ob_gzhandler().'
        if (isset ($_SERVER['HTTP_ACCEPT_ENCODING']) &&
            strpos ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') >= 0) {
            // compress output if client likes that
            @ini_set ('zlib.output_compression', 4096);
        }
        @ob_start ();

        $all_hooks = array (); // accumulates hooks from all modules
        $url_cache = CACHE_PATH . '_url_cache.json';
        
        // init loop: load php files, get definitions, get urls (hooks)
        if (file_exists ($url_cache) && 
            time () - filemtime ($url_cache) < 3600) {
            // because Spyc is slow, we cache URLs
            try { // because
                $all_hooks = json_decode (file_get_contents ($url_cache), true);
            } catch (Exception $e) {
                debug ('URL cache is corrupted: %s', $e->getMessage ());
            }
        } else { // load URLs from all handlers... and cache them.
            require_once (LIBRARY_PATH . 'spyc.php');
            foreach ($modules as $idx => $module) {
                $yaml_path = MODULE_PATH . $module . '.yaml';
                if (file_exists ($yaml_path)) {
                    try {
                        $yaml = Spyc::YAMLLoad ($yaml_path);
                        foreach ((array) $yaml['Handlers'] as 
                            $idx => $handler_array) {
                            foreach ($handler_array as $hk => $hndl) {
                                // this foreach just breaks keys from values.
                                $all_hooks[$module][$hk] = $hndl;
                            }
                        }
                    } catch (Exception $e) {
                        debug ($e->getMessage ());
                    }
                }
            }
            @file_put_contents ($url_cache, json_encode ($all_hooks));
        }

        // serve loop: load responsible controller
        try {
            $url_parts = parse_url ($_SERVER['REQUEST_URI']);
            list ($module, $handler) = get_handler_by_url ($url_parts['path']);
            include_once (MODULE_PATH . $module . '.php');
            $page = new_object (null, $module);
            $page->$handler (); // load only one page...
            die ();
        } catch (Exception $e) {
            // core error handler (not that it always works)
            debug ($e->getMessage ());
        }
    } else { // use POP as library
        @ob_start (); // prevent "failed to delete buffer" errors
    }
    // if not USE_POP_REDIRECTION, the rest of the page can be coded as usual.
?>