<?php

namespace Pop\lib;

require_once('datetime.php');
require_once('JSON.php');


function plural($num) {
    // helper for adding 's's to the end of 'hour' , 'day', etc.
    if ($num !== 1) {
        return 's';
    }
    return '';
}


function kwargs() { // come on, short round.
    $url_parts = parse_url($_SERVER['REQUEST_URI']);
    if (isset ($url_parts['query'])) {
        return (array)$url_parts['query'];
    } else {
        return [];
    }
}


function vars($index = false, $default = null) {
    // gathers everything from the request.
    static $_vars_cache_ = []; // store once, use forever

    if (!count($_vars_cache_)) { // build cache no matter what
        @session_start();
        if (!isset($_SESSION)) {
            $_SESSION = []; // can this be omitted?
        }
        $str_GET = parse_url($_SERVER['REQUEST_URI']); // $str_GET = sad byproduct of mod_rewrite
        if (isset($str_GET['query'])) {
            parse_str($str_GET['query'], $REAL_GET);
        }
        $_vars_cache_ = array_merge(
            $_COOKIE,
            (isset ($_SESSION) ? $_SESSION : []),
            (isset ($_POST) ? $_POST : []),
            (isset ($_GET) ? $_GET : []),
            (isset ($REAL_GET) ? $REAL_GET : [])
        );
    }

    if (count($_vars_cache_)) {
        if ($index === false) {
            return $_vars_cache_; // return cache if it exists
        }
        if (isset($_vars_cache_[$index])) {
            return $_vars_cache_[$index];
        }

        // everyone else would have returned by now
        return $default;
    } else {
        return []; // return nothing
    }
}


/**
 * Well, dump all variables.
 */
function dump_all() {
    var_dump(get_defined_vars());
}


function is_assoc($array) {
    // JTS on http://php.net/manual/en/function.is-array.php
    return
        is_array($array) && (!count($array) ||
            !count(array_diff_key(
                       $array, array_keys(array_keys($array))
                   )));
}


function check_keys($array, $required_keys) {
    // throw exception if the array (a=>b, c=>d, ...)
    // does not contain all values in $required_keys (a, c, ...).
    if (!is_assoc($array)) {
        $array = array_combine($array,
                               $array); // stackoverflow.com/questions/1066850/
    }

    $common_keys = array_intersect(array_keys($array), $required_keys);
    if (count($common_keys) === count($required_keys)) {
        return true;
    } else {
        throw new \Exception('Not all arguments present; needed ' . count($required_keys));
    }
}


function create_guid() {
    // http://php.net/manual/en/function.com-create-guid.php
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf(
        '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}


function create_etag($entity_contents) {
    // supply file contents and this will generate a tag.
    // http://rd2inc.com/blog/2005/03/making-dynamic-php-pages-cacheable/
    return 'ci-' . dechex(crc32($entity_contents));
}


function left($str, $pos) {
    return substr($str, 0, $pos);
}


function first($str, $fit = 100) {
    // wrapper for left with ellipses
    if (strlen($str) > $fit) {
        $str = left($str, $fit - 3) . '...';
    }

    return $str;
}


function filesize_natural($bytes) {
    # Snippet from PHP Share: http://www.phpshare.org
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } else {
        $bytes = $bytes . ' B';
    }

    return $bytes;
}


function fast_glob($path) {
    // mod: http://www.phparch.com/2010/04/putting-glob-to-the-test/
    $files = [];
    $dir = opendir($path);
    while (($currentFile = readdir($dir)) !== false) {
        if ($currentFile != '.' && $currentFile != '..') {
            $files[] = $currentFile;
        }
    }
    closedir($dir);

    return $files;
}


function println($what, $hdng = 'p') {
    if ($hdng >= 1 && $hdng <= 6) {
        $heading = 'h' . $hdng;
    } else {
        $heading = $hdng;
    }
    echo '<', $heading, '>', $what, '</', $heading, ">\n";
}


/**
 * accept multiple preg patterrns on the same string.
 * returns true if any in $patterns match $contents.
 *
 * @param $patterns
 * @param $contents
 * @return bool
 */
function preg_match_multi($patterns, $contents) {
    foreach ((array)$patterns as $pattern) {
        if (preg_match($pattern, $contents) > 0) {
            return true;
        }
    }

    return false;
}


function default_to() {
    // successively checks all supplied variables and returns the
    // first one that isn't null or empty or false or not set
    // (but 0 is valid and will be returned)
    $args = func_get_args();
    $argv = func_num_args();
    $wut = null;
    for ($i = 0; $i < $argv; $i++) {
        if (!(!isset ($args[$i]) || $args[$i] === null || $args[$i] === '' || $args[$i] === false)) {
            return $args[$i];
        }
    }

    return (!isset ($wat) || $wat === '' || $wat === null) ? $wut : $wat;
}


function array_value_key($array, $lookup) {
    // given a 1-to-1 dictionary, find the index of $value.
    foreach ((array)$array as $key => $value) {
        if ($value === $lookup) {
            return $key;
        }
    }

    return null;
}


function array_remove_values($array, $values) {
    if (!is_array($values)) {
        $values = [$values];
    }

    return array_diff($array, $values);
}


function ack_r3(&$array, $case = CASE_LOWER, $flag_rec = false) {
    // found here, no owner: http://php.net/manual/en/function.array-change-key-case.php
    $array = array_change_key_case($array, $case);
    if ($flag_rec) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                ack_r3($array[$key], $case, true);
            }
        }
    }
}
