<?php
    // Put your setup variables in vars.php. Create one if it doesn't exist.
    define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

    $_vars_cascade = array(
        dirname(dirname(PATH)) . DIRECTORY_SEPARATOR . 'vars.php',
        dirname(PATH) . DIRECTORY_SEPARATOR . 'vars.php',
        PATH . 'vars.php',
        PATH . 'vars.default.php',
    );
    foreach($_vars_cascade as $var_file) {
        if (file_exists($var_file)) {
            require_once($var_file);
            break;
        }
    }
    require_once(PATH . 'lib.php');

    // run!
    import('pop');
    $pop = new Pop(); // register autoloaders and URL handlers
    unset($pop);