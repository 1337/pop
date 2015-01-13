<?php
/**
 * JSON utilities.
 * User: brian
 * Date: 11/01/15
 * Time: 6:19 PM
 */

namespace Pop\lib;


/**
 * Because this is how smart PHP is now. Return null, call another function
 * to the the actual error.
 *
 * @return string
 */
function jsonLastError() {
    // modified from http://php.net/manual/en/function.json-last-error.php
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return 'No errors';
        case JSON_ERROR_DEPTH:
            return 'Maximum stack depth exceeded';
        case JSON_ERROR_STATE_MISMATCH:
            return 'Underflow or the modes mismatch';
        case JSON_ERROR_CTRL_CHAR:
            return 'Unexpected control character found';
        case JSON_ERROR_SYNTAX:
            return 'Syntax error, malformed JSON';
        case JSON_ERROR_UTF8:
            return 'Malformed UTF-8 characters, possibly incorrectly encoded';
        default:
            return 'Unknown error';
    }
}

/**
 * @param $path
 * @return array
 * @throws \Exception
 */
function readJSON($path) {
    $file_contents = file_get_contents($path);
    // json_decode: true = array, false = object, @return null = failure
    $props = json_decode($file_contents, true);
    if ($props === null) { // if fails
        $msg = jsonLastError();
        throw new \Exception("Could not deserialize $path: $msg");
    }

    return $props;
}


/**
 * @param $path
 * @param $object
 * @return string
 * @throws \Exception
 */
function writeJSON($path, $object) {
    $file_contents = json_encode($object, JSON_PRETTY_PRINT);
    if ($file_contents === false) {
        throw new \Exception("Could not serialize $path");
    }

    $res = file_put_contents($path, $file_contents, LOCK_EX);
    if ($res === false) {
        $msg = jsonLastError();
        throw new \Exception("Could not write into $path: $msg");
    }

    return $file_contents;
}


/**
 * Class JSON
 * OOP wrapper for what's above.
 */
class JSON {
    private $path;

    public function __construct($path) {
        $this->path = $path;
    }
    
    public function read() {
        return readJSON($this->path);
    }
    
    public function write($object) {
        return writeJSON($this->path, $object);
    }
}