<?php

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

    function _date ($component = 'second', $date) {
        // date() with even more formats.
        $conversion_table = array (
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
        return date ($v, $date);
    }

    function break_date ($date) {
        // date breakdown.
        return array (
            'year'  => _date ('year',  $date),
            'month' => _date ('month', $date),
            'day'   => _date ('day',   $date)
        );
    }

    function last_day_of_month ($month, $year) {
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

    // for public good
    function php_date_to_mysql_datetime ($date) {
        return date('Y-m-d H:i:s',$date);
    }

    function mysql_datetime_to_php_date ($datetime) {
        return strtotime($datetime);
    }

    function time_diff_from_now ($datetime) {
        // accepts php date.
        // returns some relative time from now.

        // get time now in unix seconds since epoch.
        $now_u = date ('U');
        // get time (datetime) in unix seconds since epoch.
        $datetime_u = mktime (
            _date ('hour',   $datetime),
            _date ('minute', $datetime),
            _date ('second', $datetime),
            _date ('month',  $datetime),
            _date ('day',    $datetime),
            _date ('year',   $datetime)
        );
        return $now_u - $datetime_u;
    }

    function plural ($num) {
        // helper for adding 's's to the end of 'hour' , 'day', etc.
        if ($num != 1) {
            return 's';
        }
    }

    function human_time_diff ($datetime, $datetime2 = -1, $suffix = ' ago') {
        // mod of http://snipplr.com/view/4912/relative-time/
        // $diff = time_diff_from_now ($datetime);
        if ($datetime2 == -1) {
            $datetime2 = time();
        }
        $diff = abs ($datetime2 - $datetime);
        if ($diff < 60) {
            return $diff . ' second' . plural($diff) . $suffix;
        }
        $diff = round($diff/60);
        if ($diff < 60) {
            return $diff . ' minute' . plural($diff) . $suffix;
        }
        $diff = round($diff/60);
        if ($diff < 24) {
            return $diff . ' hour' . plural($diff) . $suffix;
        }
        $diff = round($diff/24);
        if ($diff < 7) {
            return $diff . ' day' . plural($diff) . $suffix;
        }
        $diff = round($diff/7);
        if ($diff < 4) {
            return $diff . ' week' . plural($diff) . $suffix;
        }
        if ($datetime > 0) { // it'll return the epoch if not
            return 'on ' . date('F j, Y', strtotime($datetime));
        }
    }