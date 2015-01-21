<?php
/**
 * Created by PhpStorm.
 * User: brian
 * Date: 20/01/15
 * Time: 9:54 PM
 */


namespace Pop\controllers;

/**
 * Returns true if object meets filter criteria.
 *
 * @param $object
 * @param $filter: e.g. ['name ==', 'brian']
 * @return bool
 * @throws \Exception
 */
function filter($object, $filter) {
    $cond = $filter[1]; // e.g. '5'
    if (strpos($filter[0], ' ') !== false) {
        // if space is found in 'name ==', then split by it
        $spl = explode(' ', $filter[0]);
        // >, <, ==, ===, !=, <=, >=, IN, WITHIN, or CONTAINS
        $mode = $spl[1];
        $field = $spl[0];
    } else {
        // else guess by getting last two characters or something
        // >, <, ==, !=, <=, >=, or IN
        $mode = trim(substr($filter[0], -2));
        $field = trim(substr($filter[0], 0, strlen($filter[0]) - strlen($mode)));
    }
    $haystack = $object->{$field}; // good name
    switch ($mode) {
        case '>';
        case 'GT';
            return ($haystack > $cond);
        case '>=':
        case 'GTE';
            return ($haystack >= $cond);
        case '<';
        case 'LT';
            return ($haystack < $cond);
        case '<=';
        case 'LTE';
            return ($haystack <= $cond);
        case '=':
        case '==':
        case 'EQV';
            if (is_string($haystack) && is_string($cond)) {
                // case-insensitive comparison
                return (strcasecmp($haystack, $cond) === 0);
            } else {
                return ($haystack == $cond);
            }
        case '===';
        case 'EQ';
            return ($haystack === $cond);
        case '!=';
        case 'NEQ';
            return ($haystack != $cond);
        case 'WITHIN'; // within; $cond must be [min, max]
        case 'RANGE';
            return ($haystack >= $cond[0] && $haystack <= $cond[1]);
        case 'IN': // list of criteria supplied contains this field's value
            if (is_string($cond)) {
                return (strpos($cond,
                               $haystack) >= 0); // 'is found in the condition'
            } else { // compare as array
                return (in_array($haystack, $cond));
            }
        case 'CONTAINS': // reverse IN; this field's value is an array that contains the criterion
            if (is_string($haystack)) {
                // 'condition is found in db field'
                return (strpos($haystack, $cond) >= 0);
            } else { // compare as array
                return (in_array($cond, $haystack));
            }
        default:
            throw new \Exception("'$mode' is not a recognized filter mode");
            break;
    }
}