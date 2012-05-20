<?php
    include_once (LIBRARY_PATH . 'import.php');
    import('datetime', 'header');

    if (!function_exists ('kwargs')) {
        function kwargs() { // come on, short round.
            $url_parts = parse_url ($_SERVER['REQUEST_URI']);
            if (isset ($url_parts['query'])) {
                return (array) $url_parts['query'];
            } else {
                return array();
            }
        }
    }

    if (!function_exists('vars')) {
        function vars ($index = false, $default = null) {
            // gathers everything from the request.
            static $_vars_cache_ = array(); // store once, use forever

            if (!sizeof($_vars_cache_)) { // build cache no matter what
                @session_start();
                if (!isset($_SESSION)) {
                    $_SESSION = array(); // can this be omitted?
                }
                $str_GET = parse_url($_SERVER['REQUEST_URI']); // $str_GET = sad byproduct of mod_rewrite
                if (isset($str_GET['query'])) {
                    parse_str ($str_GET['query'], $REAL_GET);
                }
                $_vars_cache_ = array_merge (
                    $_COOKIE,
                    (isset ($_SESSION)? $_SESSION : array()),
                    (isset ($_POST)   ? $_POST    : array()),
                    (isset ($_GET)    ? $_GET     : array()),
                    (isset ($REAL_GET)? $REAL_GET : array())
                );
            }

            if (sizeof($_vars_cache_)) {
                if ($index === false) {
                    return $_vars_cache_; // return cache if it exists
                }
                if (isset($_vars_cache_[$index])) {
                    return $_vars_cache_[$index];
                }

                // everyone else would have returned by now
                return $default;
            } else {
                return array(); // return nothing
            }
        }
    }

    if (!function_exists ('is_assoc')) {
        function is_assoc ($array) {
            // JTS on http://php.net/manual/en/function.is-array.php
            return
                is_array ($array) && (!count ($array) ||
                    !count (array_diff_key (
                        $array, array_keys (array_keys ($array))
                    )));
        }
    }

    if (!function_exists ('check_keys')) {
        function check_keys ($array, $required_keys) {
            // throw exception if the array (a=>b, c=>d, ...)
            // does not contain all values in $required_keys (a, c, ...).
            if (!is_assoc ($array)) {
                $array = array_combine($array, $array); // stackoverflow.com/questions/1066850/
            }

            $common_keys = array_intersect (array_keys ($array), $required_keys);
            if (sizeof ($common_keys) === sizeof ($required_keys)) {
                return true;
            } else {
                throw new Exception('Not all arguments present; needed ' . sizeof ($required_keys));
            }
        }
    }

    if (!function_exists ('create_guid')) {
        function create_guid() {
            // http://php.net/manual/en/function.com-create-guid.php
            if (function_exists ('com_create_guid')) {
                return trim (com_create_guid(), '{}');
            }
            return sprintf (
                '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand (0, 65535),
                mt_rand (0, 65535),
                mt_rand (0, 65535),
                mt_rand (16384, 20479),
                mt_rand (32768, 49151),
                mt_rand (0, 65535),
                mt_rand (0, 65535),
                mt_rand (0, 65535)
            );
        }
    }

    if (!function_exists ('create_etag')) {
        function create_etag ($entity_contents) {
            // supply file contents and this will generate a tag.
            // http://rd2inc.com/blog/2005/03/making-dynamic-php-pages-cacheable/
            return 'ci-' . dechex (crc32 ($entity_contents));
        }
    }

    if (!function_exists ('left')) {
        function left ($str,$pos) {
            return substr($str,0,$pos);
        }
    }

    if (!function_exists ('first')) {
        function first ($str, $fit = 100) {
            // wrapper for left with ellipses
            if (strlen ($str) > $fit) {
                $str = left ($str, $fit - 3) . '...';
            }
            return $str;
        }
    }

    if (!function_exists ('filesize_natural')) {
        function filesize_natural ($bytes) {
            # Snippet from PHP Share: http://www.phpshare.org
            if ($bytes >= 1073741824) {
                $bytes = number_format ($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                $bytes = number_format ($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                $bytes = number_format ($bytes / 1024, 2) . ' KB';
            } else {
                $bytes = $bytes . ' B';
            }
            return $bytes;
        }
    }

    if (!function_exists ('fast_glob')) {
        function fast_glob ($path) {
            // mod: http://www.phparch.com/2010/04/putting-glob-to-the-test/
            $files = array();
            $dir = opendir ($path);
            while (($currentFile = readdir ($dir)) !== false) {
                if ( $currentFile != '.' && $currentFile != '..' ) {
                    $files[] = $currentFile;
                }
            }
            closedir ($dir);
            return $files;
        }
    }

    if (!function_exists ('println')) {
        function println ($what, $hdng = 'p') {
            if ($hdng >= 1 && $hdng <= 6) {
                $heading = 'h' . $hdng;
            } else {
                $heading = $hdng;
            }
            echo '<',$heading,'>',$what,'</',$heading,">\n";
        }
    }

    if (!function_exists ('auth_curl')) {
        function auth_curl ($url, $user, $pass, $protocol = 'http') {
            // stackoverflow.com/questions/2140419
            // $protocol doesn't work
            if (!function_exists('curl_init')) die('Error: cURL does not exist! Please install cURL.');
            $process = curl_init ($url);

            $options = array (
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERPWD => $user . ':' . $pass,
                CURLOPT_URL => $url,
            );

            curl_setopt_array ($process, $options);
            if (!curl_exec($process)) die(curl_error ($process));
            $data = curl_multi_getcontent ($process);
            curl_close ($process);
            return $data;
        }
    }

    if (!function_exists ('async_curl')) {
        function async_curl ($url, $params) {
            // stackoverflow.com/questions/962915
            foreach ($params as $key => &$val) {
              if (is_array($val)) $val = implode (',', $val);
                $post_params[] = $key . '=' . urlencode ($val);
            }
            $post_string = implode ('&', $post_params);

            $parts = parse_url ($url);

            $fp = fsockopen (
                $parts['host'],
                isset ($parts['port']) ? $parts['port'] : 80,
                $errno,
                $errstr,
                30
            );

            $out = 'POST '.$parts['path']." HTTP/1.1\r\n";
            $out.= 'Host: '.$parts['host']."\r\n";
            $out.= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
            $out.= 'Content-Length: '.strlen($post_string)."\r\n";
            $out.= 'Connection: Close' . "\r\n\r\n";
            if (isset($post_string)) $out.= $post_string;

            fwrite ($fp, $out);
            fclose ($fp);
        }
    }

    if (!function_exists ('map_reduce')) {
        function map_reduce ($array, $callback,
                              $drop_results = false,
                              $map_url = false,
                              $reduce_url = false) {
            /*  maps the array an the operation to different PHP processes/threads
                known as shards, then reduces them back to one array or value.
                function referenced by callback just be present in all threads.
                $drop_results = true issues calls to the map_url, but does not
                wait for these functions to return.
            */
        }
    }

    if (!function_exists ('multi_http')) {
        // function connects to an array of URLS at the same time
        // and returns an array of results.

        function multi_http ($urlArr) {
            $sockets = $urlInfo = $retDone = $retData = $errno = $errstr = array();
            for ($x = 0; $x < count ($urlArr); $x++) {
                $urlInfo[$x] = parse_url($urlArr[$x]);
                $urlInfo[$x][port] = ($urlInfo[$x][port]) ? $urlInfo[$x][port] : 80;
                $urlInfo[$x][path] = ($urlInfo[$x][path]) ? $urlInfo[$x][path] : '/';
                $sockets[$x] = fsockopen ($urlInfo[$x][host], $urlInfo[$x][port], $errno[$x], $errstr[$x], 30);
                socket_set_blocking ($sockets[$x], false);
                $query = ($urlInfo[$x][query]) ? '?' . $urlInfo[$x][query] : '';
                fputs ($sockets[$x],'GET ' . $urlInfo[$x][path] . "$query HTTP/1.0\r\nHost: " . $urlInfo[$x][host] . "\r\n\r\n");
            }
            // ok read the data from each one
            $done = false;
            while (!$done) {
                for ($x = 0; $x < count($urlArr); $x++) {
                    if (!feof ($sockets[$x])) {
                        if ($retData[$x]) {
                            $retData[$x] .= fgets ($sockets[$x], 128);
                        } else {
                            $retData[$x] = fgets ($sockets[$x], 128);
                        }
                    } else {
                        $retDone[$x] = 1;
                    }
                }
                $done = (array_sum ($retDone) === count ($urlArr));
            }
            return $retData;
        }
    }

    if (!function_exists ('preg_match_multi')) {
        function preg_match_multi($patterns, $contents) {
            // accept multiple preg patterrns on the same string.
            // returns true of any in $patterns match $contents.
            foreach ((array) $patterns as $pattern) {
                if (preg_match ($pattern, $contents) > 0) {
                    return true;
                }
            }
        }
    }

    if (!function_exists ('default_to')) {
        function default_to() {
            // successively checks all supplied variables and returns the
            // first one that isn't null or empty or false or not set
            // (but 0 is valid and will be returned)
            $args = func_get_args();
            $argv = func_num_args();
            for ($i = 0; $i < $argv; $i ++) {
                if (! (!isset ($args[$i]) || $args[$i] === null || $args[$i] === '' || $args[$i] === false)) {
                    return $args[$i];
                }
            }
            return (!isset ($wat) || $wat === '' || $wat === null) ? $wut : $wat;
        }
    }

    if (!function_exists ('array_value_key')) {
        function array_value_key ($array, $lookup) {
            // given a 1-to-1 dictionary, find the index of $value.
            foreach ((array) $array as $key => $value) {
                if ($value === $lookup) {
                    return $key;
                }
            }
            return null;
        }
    }

    if (!function_exists ('array_remove_values')) {
        function array_remove_values ($array, $values) {
            if (!is_array ($values)) {
                $values = array ($values);
            }
            return array_diff ($array, $values);
        }
    }

    if (!function_exists ('ack_r3')) {
        function ack_r3 (&$array, $case=CASE_LOWER, $flag_rec=false) {
            // found here, no owner: http://php.net/manual/en/function.array-change-key-case.php
            $array = array_change_key_case ($array, $case);
            if ($flag_rec) {
                foreach ($array as $key => $value) {
                    if (is_array ($value)) {
                        ack_r3 ($array[$key], $case, true);
                    }
                }
            }
        }
    }

    if (!function_exists ('escape_data')) {
        function escape_data ($data) {
            global $slink;
            if (ini_get('magic_quotes_gpc')) {
                $data = stripslashes($data);
            }
            if ($slink && function_exists ('mysql_real_escape_string')) {
                return mysql_real_escape_string (trim ($data), $slink);
            } else {
                return addslashes (trim ($data));
            }
        }
    }