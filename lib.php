<?php
    function kwargs () { // come on, short round.
        $url_parts = parse_url ($_SERVER['REQUEST_URI']);
        if (array_key_exists ('query', $url_parts)) {
            return (array) $url_parts['query'];
        } else {
            return array ();
        }
    }

    $_vars_cache_ = array ();
    function vars ($index = false, $default = null) {
        // gathers everything from the request.
        global $_vars_cache_; // store once, use forever

        if (sizeof ($_vars_cache_) > 0) {
            if ($index) {
                if (array_key_exists ($index, $_vars_cache_)) {
                    return $_vars_cache_[$index];
                } else {
                    return $default;
                }
            } else {
                return $_vars_cache_; // return cache if it exists
            }
        } else {
            // $str_GET = sad byproduct of mod_rewrite
            $str_GET = parse_url ($_SERVER['REQUEST_URI']);
            if (array_key_exists ('query', $str_GET)) {
                parse_str($str_GET['query'], $REAL_GET);
            } else {
                $REAL_GET = array ();
            }
            @session_start ();
            if (isset ($_SESSION)) {
                $vars = array_merge ($_COOKIE, $_SESSION, $_POST, $_GET, $REAL_GET);
            } else {
                $vars = array_merge ($_COOKIE, $_POST, $_GET, $REAL_GET);
            }

            $_vars_cache_ = $vars; // cache the variables
            if ($index) {
                if (array_key_exists ($index, $vars)) {
                    return $vars[$index];
                } else { // ono...
                    return $default;
                }
            } else {
                // die(var_export($vars, true));
                return $vars;
            }
        }
    }
    
    function is_assoc ($array) {
        // JTS on http://php.net/manual/en/function.is-array.php
        return (is_array ($array) &&
            (count ($array) == 0 ||
                0 !== count (array_diff_key (
                    $array,
                    array_keys (array_keys ($array))
                ))
            )
        );
    }

    function check_keys ($array, $required_keys) {
        // throw exception if the array (a=>b, c=>d, ...)
        // does not contain all values in $required_keys (a, c, ...).
        if (!is_assoc ($array)) {
            $array = array_combine($array, $array); // stackoverflow.com/questions/1066850/
        }
        
        $common_keys = array_intersect (array_keys ($array), $required_keys);
        if (sizeof ($common_keys) == sizeof ($required_keys)) {
            return true;
        } else {
            throw new Exception('Not all arguments present; needed ' . sizeof ($required_keys));
        }
    }
    
    function debug ($msg, $fancy = true) {
        // fancy not implemented
        echo ("<p>Error: $msg</p>");
    }
    
    function get_handler_by_url ($url, $include_module = false) {
        // provide the name of the handler that serves a given url.
        global $all_hooks;
        if (isset ($all_hooks) && is_array ($all_hooks)) {
            foreach ($all_hooks as $module => $hooks) {
                foreach ($hooks as $hook => $handler) {
                    $url_parts = parse_url ($url);
                    if ($url_parts) { // On malformed URLs, parse_url() may return FALSE
                        $match = preg_match (
                            '#^/' . SUBDIR . $hook . '$#i', 
                            $url_parts['path']
                        );
                        if ($match) { // 1 = match
                            return $include_module ? 
                                "$module.$handler" : 
                                array ($module, $handler); // superclass function
                        }
                    }
                }
            }        
        }
    }
    
    function auth_curl ($url, $user, $pass, $protocol = 'http') {
        // stackoverflow.com/questions/2140419
        // $protocol doesn't work
        if (!function_exists('curl_init')) die("Error: cURL does not exist! Please install cURL.");
        $process = curl_init ($url);

        $options = array (
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERPWD => "$user:$pass",
            CURLOPT_URL => $url,
        );

        curl_setopt_array ($process, $options);
        if (!curl_exec($process)) die(curl_error ($process));
        $data = curl_multi_getcontent ($process);
        curl_close ($process);
        return $data;
    }

?>
