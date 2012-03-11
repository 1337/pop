<?php
    if (@file_exists (dirname (__FILE__) . '/vars.php')) {
        // put your setup variables in this file
        require_once (dirname (__FILE__) . '/vars.php');
    } else {
        // defaults kick in only if you did not set up properly
        require_once (dirname (__FILE__) . '/vars.default.php');
    }
    
    require_once (dirname (__FILE__) . '/lib.php');

    // TODO: events, access levels, perm checks, relationships
    // TODO: loose coupling (allow modules to only notify the core to induce custom-named events)
    // TODO: query indices
    // TODO: non-random GUID hash object storage
    
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
        $url_cache = DATA_PATH . '_url_cache.json';
        
        if (@file_exists ($url_cache)) { // because Spyc is slow, we cache URLs
            try { // because
                $all_hooks = json_decode (file_get_contents ($url_cache), true);
            } catch (Exception $e) {
                debug (sprintf ("URL cache is corrupted: %s", $e->getMessage ()));
            }
        } else { // load URLs from all handlers... and cache them.
            require_once (LIBRARY_PATH . '/spyc.php');
            // init loop: load php files, get definitions, get urls (hooks)
            foreach ($modules as $module) { // modules is in (default_)vars.php
                $path = "/$module.php";
                $yaml_path = "/$module.yaml";
                if (@file_exists (MODULE_PATH . $yaml_path) && 
                    !class_exists ($module)) {
                    try {
                        $yaml = Spyc::YAMLLoad (MODULE_PATH . $yaml_path);
                        foreach ((array) $yaml['Handlers'] as $handler_array) {
                            foreach ($handler_array as $hk => $hndl) {
                                // this foreach just breaks keys from values.
                                $all_hooks[$module][$hk] = $hndl;
                            }
                        }
                    } catch (Exception $e) {
                        debug (sprintf ("%s", $e->getMessage ()));
                    }
                }
            }
            file_put_contents ($url_cache, json_encode ($all_hooks));
        }

        // serve loop: load responsible controller
        try {
            $url_parts = parse_url ($_SERVER['REQUEST_URI']);
            list ($module, $handler) = get_handler_by_url ($url_parts['path']);
            @include_once (MODULE_PATH . $module . '.php'); // modules are the php classes
            $page = new_object (null, $module);
            $page->$handler (); // load only one page...
            die ();
        } catch (Exception $e) {
            // core error handler (not that it always works)
            debug ($e->getMessage ());
        }
    } else { // use POP as library
        foreach ($modules as $module) { // modules is in (default_)vars.php
            $path = "$module.php";
            if (@file_exists (MODULE_PATH . $path)) {// && !class_exists ($module)) {
                @include_once (MODULE_PATH . $path); // modules are the php classes
            }
        }
        @ob_start (); // prevent "failed to delete buffer" errors
    }
    
    // if not USE_POP_REDIRECTION, the rest of the page can be coded as usual.
?>
