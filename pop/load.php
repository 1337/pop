<?php

namespace Pop;

// Put your setup variables in vars.php. Create one if it doesn't exist.
define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once('lib/PathResolver.php');

// traverses up the path until the nearest vars.php is found,
// and then require it.
$_resolver = new lib\PathResolver(PATH); $_i = 0;
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

require_once('lib/lib.php');
require_once('modules/Pop.php');
