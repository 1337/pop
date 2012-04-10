<?php
    define ('PATH', dirname (__FILE__) . '/');
    // put your setup variables in this file
    // defaults kick in only if you did not set up properly
    require_once (PATH . (file_exists (PATH . 'vars.php')? 
                          'vars.php':
                          'vars.default.php')
    );
    require_once (PATH . 'lib.php');
    require_once (MODULE_PATH . 'pop.php');
    
    $pop = new Pop (); unset ($pop); // register autoloaders
?>