<?php
    require_once ('vars.php');
    require_once ('lib.php');
    // TODO: events, access levels, perm checks, relationships
    // TODO: loose coupling (allow modules to only notify the core to induce custom-named events)
    // TODO: let core handle errors, not modules
    
    @ob_start ();
    @chmod (DATA_PATH, 0777);
    if (!is_writable (DATA_PATH)) {
        die ("data path not writable");
    }
    
    $all_hooks = array (); // accumulates hooks from all modules
    // init loop: load php files, get definitions, get urls (hooks)
    foreach ($modules as $module) { // modules is in (default_)vars.php
        $path = "modules/$module.php";
        if (file_exists ($path)) {
            include_once ($path); // modules are the php classes
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
    list ($module, $handler) = get_handler_by_url ($url_parts['path']);
    try {
        $page = new_object (null, $module);
        $page->$handler (); // superclass function
        exit (); // load only one page...
    } catch (Exception $e) {
        debug ($e->getMessage ()); // you fail at life
    }

    debug ("No handler serves " . $_SERVER['REQUEST_URI'] . ".");
?>