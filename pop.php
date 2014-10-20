<?php

namespace Pop;

// Put your setup variables in vars.php. Create one if it doesn't exist.
define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once(PATH . 'lib/PathResolver.php');

// traverses up the path until the nearest vars.php is found,
// and then require it.
$_resolver = new PathResolver(PATH); $_i = 0;
do {
    $_resolver = $_resolver('vars.php');
    if ($_resolver->exists) {
        // echo "$_resolver exists \n";
        require_once($_resolver);
        break;
    }
    $_resolver = $_resolver->parent->parent;
} while ($_i++ < 10);

// TODO: die if no vars.php found

// import libraries (including the import() function)
require_once(LIBRARY_PATH . 'import.php');
import('lib');


// run!
$pop = import('pop');  // register autoloaders and URL handlers
