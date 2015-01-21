<?php
/**
 * Created by PhpStorm.
 * User: brian
 * Date: 20/01/15
 * Time: 10:27 PM
 */

namespace Pop\lib\warnings;


function startCatchWarnings() {
    // ? what is this bollocks: http://stackoverflow.com/questions/1241728/
    set_error_handler(
        function ($errno, $errstr, $errfile, $errline, array $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }
            throw new \Exception($errstr, 0, $errno, $errfile, $errline);
        });
}


function endCatchWarnings() {
    restore_error_handler();
}
