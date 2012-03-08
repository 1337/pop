<?php
    if (file_exists (dirname (__FILE__) . '/vars.php')) {
        require_once (dirname (__FILE__) . '/vars.php'); // put your setup variables in this file
    } else {
        // defaults kick in only if you did not set up properly
        require_once (dirname (__FILE__) . '/vars.default.php');
    }
    
    require_once (dirname (__FILE__) . '/lib.php');
    require_once (dirname (__FILE__) . '/lib/spyc.php');

    // TODO: events, access levels, perm checks, relationships
    // TODO: loose coupling (allow modules to only notify the core to induce custom-named events)
    
    define ('EXEC_START_TIME', microtime (true));
    if (USE_POP_REDIRECTION === true) {
        // "Also note that using zlib.output_compression is preferred over ob_gzhandler()."
        if (isset ($_SERVER['HTTP_ACCEPT_ENCODING']) &&
            strpos ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') >= 0) {
            // compress output if client likes that
            @ini_set ("zlib.output_compression", 4096);
        }
        @ob_start ();

        $all_hooks = array (); // accumulates hooks from all modules
        // init loop: load php files, get definitions, get urls (hooks)
        foreach ($modules as $module) { // modules is in (default_)vars.php
            $path = "modules/$module.php";
            $yaml_path = "modules/$module.yaml";
            if (@file_exists (dirname (__FILE__) . '/' . $yaml_path) && !class_exists ($module)) {
                try {
                    $yaml = Spyc::YAMLLoad(dirname (__FILE__) . '/' . $yaml_path);
                    if (isset ($yaml['Handlers'])) {
                        foreach ($yaml['Handlers'] as $handler_array) {
                            foreach ($handler_array as $hk => $hndl) {
                                // this foreach just breaks keys from values.
                                $all_hooks[$module][$hk] = $hndl;
                            }
                        }
                    }
                    unset ($yaml);
                } catch (Exception $e) {
                    debug (sprintf ("%s", $e->getMessage ()));
                }
            }
        }

        // serve loop: load responsible controller
        $url_parts = parse_url ($_SERVER['REQUEST_URI']);
        try {
            list ($module, $handler) = get_handler_by_url ($url_parts['path']);
            include_once (MODULE_PATH . $module . '.php'); // modules are the php classes
            $page = new_object (null, $module);
            $page->$handler (); // superclass function
            exit (); // load only one page...
        } catch (Exception $e) {
            // you fail at life
            debug (sprintf ("%s", $e->getMessage ()));
        }
    } else { // use POP as library
        foreach ($modules as $module) { // modules is in (default_)vars.php
            $path = "modules/$module.php";
            if (@file_exists (dirname (__FILE__) . '/' . $path) && !class_exists ($module)) {
                @include_once (dirname (__FILE__) . '/' . $path); // modules are the php classes
            }
        }
        @ob_start ();
    }
    
    // if not USE_POP_REDIRECTION, the rest of the page can be coded as usual.
?>
