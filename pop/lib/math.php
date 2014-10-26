<?php
// random crap you can use

namespace Pop\lib;

define ('PI', 3.141592654);

function factorial($n) {
    // => $n!
    // can also return INF
    $n = (int)$n;
    if ($n <= 1) {
        return 1;
    }

    return $n * factorial($n - 1);
}

function binomialCoefficient($n, $r) {
    // $n is supposed to be the larger number: nCr
    if ($n < $r) { // auto-correct params
        list ($n, $r) = [$r, $n];
    }

    return (factorial($n) / factorial($r)) / factorial($n - $r);
}

function probabilityMassFunction($k, $n, $p) {
    // binomial distribution function
    // $k = number of successes
    // $n = number of trials
    // $p = probability of each trial (0~1)
    // returns probability that something is $k given $n tries
    if ($n < $k) { // auto-correct params
        list($n, $k) = [$k, $n];
    }

    return binomialCoefficient($n, $k) *
    pow($p, $k) *
    pow(1 - $p, $n - $k);
}

function zScore($x, $u, $s) {
    // 'get z from x'
    // $x = raw score
    // $u = mean of the pop
    // $s = standard deviation of the pop
    return ($x - $u) / $s;
}

;

function probabilityDensityFunction($x, $u, $s) {
    // normal distribution function
    // $x = raw score
    // $u = mean of the pop
    // $s = standard deviation of the pop
    return (1 / $s / sqrt(2 * PI)) * exp(-1 / 2 * pow($x - $u, 2)
                                         / pow($s, 2));
}
