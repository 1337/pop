<?php
    if (file_exists (dirname (__FILE__) . '/vars.php')) {
        require_once (dirname (__FILE__) . '/vars.php'); // put your setup variables in this file
    } else {
        // defaults kick in only if you did not set up properly
        require_once (dirname (__FILE__) . '/vars.default.php');
    }
    
    require_once (dirname (__FILE__) . '/lib.php');
    // TODO: events, access levels, perm checks, relationships
    // TODO: loose coupling (allow modules to only notify the core to induce custom-named events)
    // TODO: let core handle errors, not modules
    
    define ('EXEC_START_TIME', microtime ());
    if (USE_POP_REDIRECTION === true) {
        // "Also note that using zlib.output_compression is preferred over ob_gzhandler()."
        @ini_set ("zlib.output_compression", 4096);
        @ob_start ();
        header("Cache-Control: maxage=9999999");

        $all_hooks = array (); // accumulates hooks from all modules
        // init loop: load php files, get definitions, get urls (hooks)
        foreach ($modules as $module) { // modules is in (default_)vars.php
            $path = "modules/$module.php";
            if (file_exists (dirname (__FILE__) . '/' . $path) && !class_exists ($module)) {
                include_once (dirname (__FILE__) . '/' . $path); // modules are the php classes
                $get_urls = (array) get_class_vars ($module);
                if (array_key_exists ('urls', $get_urls)) {
                    $hooks = $get_urls['urls'];
                } else {
                    $hooks = array (); // also, a loop reset
                }
                $all_hooks[$module] = $hooks;
            }
        }

        // serve loop: load responsible controller
        $url_parts = parse_url ($_SERVER['REQUEST_URI']);
        try {
            list ($module, $handler) = get_handler_by_url ($url_parts['path']);
            $page = new_object (null, $module);
            $page->$handler (); // superclass function
            exit (); // load only one page...
        } catch (Exception $e) {
            debug (sprintf (
                "%s %s %d",
                $e->getMessage (),
                $e->getFile (),
                $e->getLine ()
            )); // you fail at life
        }
    } else { // use POP as library
        foreach ($modules as $module) { // modules is in (default_)vars.php
            $path = "modules/$module.php";
            if (file_exists (dirname (__FILE__) . '/' . $path) && !class_exists ($module)) {
                include_once (dirname (__FILE__) . '/' . $path); // modules are the php classes
            }
        }
        @ob_start ();
    }
    
    // if not USE_POP_REDIRECTION, the rest of the page can be coded as usual.
?>