<?php

namespace Pop;

const VERSION = '1.0';

/**
 * has something to do with spl_autoload_register.
 *
 * http://php.net/manual/en/function.autoload.php
 * @param $classname
 */
function __autoload($className) {
    $paths = [
        PATH,
        PATH . 'lib' . DIRECTORY_SEPARATOR,
        PATH . 'modules' . DIRECTORY_SEPARATOR,
    ];

    $className = str_replace(__NAMESPACE__ . '\\', '', $className);  // remove Pop\
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    foreach ($paths as $path) {
        $fullyQualifiedPath = "$path$className.php";
        // print "$fullyQualifiedPath \n";
        if (!file_exists($fullyQualifiedPath)) {
            continue;
        }
        require_once($fullyQualifiedPath);
    }
}
spl_autoload_register(__NAMESPACE__ . '\__autoload');