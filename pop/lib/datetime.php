<?php

namespace Pop\lib;

// changed fed
function smktime($mo, $day, $year) {
    //shorthand. replace triple leading 0s wit this
    // returns unix timestamp
    return mktime(0, 0, 0, $mo, $day, $year);
}

function alt_date($date, $sec, $min, $ho, $mon, $day, $year) {
    // returns unix timestamp
    $newdate = mktime(date('H', $date) + $ho,
                      date('i', $date) + $min,
                      date('s', $date) + $sec,
                      date('m', $date) + $mon,
                      date('d', $date) + $day,
                      date('Y', $date) + $year);

    return $newdate;
}

function salt_date($date, $mon, $day, $year) {
    // another shorthand, sir
    // returns unix timestamp.
    return alt_date($date, 0, 0, 0, $mon, $day, $year);
}

function _date($component = 'second', $date) {
    // date() with even more formats.
    $conversion_table = array(
        'second'     => 's',
        'minute'     => 'i',
        'hour'       => 'G',
        'day'        => 'd',
        'month'      => 'm',
        'month_name' => 'F',
        'year'       => 'Y'
    );
    $v = $component; // compat with date()
    if (isset ($conversion_table[$component])) {
        $v = $conversion_table[$component];
    }

    return date($v, $date);
}

function break_date($date) {
    // date breakdown.
    return array(
        'year'  => _date('year', $date),
        'month' => _date('month', $date),
        'day'   => _date('day', $date)
    );
}


function last_day_of_month($month, $year) {
    $oldj = 0;

    for ($i = 1; $i < 32; ++$i) {
        // loop until the day number decreases ('new month')
        $newj = date('d', smktime($month, $i, $year));
        if ($newj < $oldj) {
            break;
        }
        $oldj = $newj;
    }

    return $i - 1;
}
