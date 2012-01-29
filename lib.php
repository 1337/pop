<?php
    function kwargs () { // come on, short round.
        $url_parts = parse_url ($_SERVER['REQUEST_URI']);
        return (array) $url_parts['query'];
    }

    function vars ($index = false) {
        // gathers everything from the request.
        
        // $str_GET = sad byproduct of mod_rewrite
        $str_GET = parse_url ($_SERVER['REQUEST_URI']);
        if (array_key_exists ('query', $str_GET)) {
            parse_str($str_GET['query'], $REAL_GET);
        } else {
            $REAL_GET = array ();
        }
        if (isset ($_SESSION)) {
            $vars = array_merge ($_COOKIE, $_SESSION, $_POST, $_GET, $REAL_GET);
        } else {
            $vars = array_merge ($_COOKIE, $_POST, $_GET, $REAL_GET);
        }
        if ($index) {
            return $vars[$index];
        } else {
            return $vars;
        }
    }
    
    function check_keys ($array, $required_keys) {
        // throw exception if the array (a=>b, c=>d, ...)
        // does not contain all values in $required_keys (a, c, ...).
        $common_keys = array_intersect (array_keys ($array), $required_keys);
        if (sizeof ($common_keys) == sizeof ($required_keys)) {
            return true;
        } else {
            throw new Exception('Not all arguments present; needed ' . sizeof ($required_keys));
        }
    }
    
    function debug ($msg, $fancy = true) {
        // fancy not implemented
        echo ("<p>hmm: $msg</p>");
    }
    
    function css_compress ($h) {
        /* remove comments */
        $h = preg_replace ('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $h);
        /* remove tabs, spaces, newlines, etc. */
        $h = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $h);
     
        return $h;
    }

    function php_compress ($h) {
        $h = str_replace ("<?php", '<?php ', $h);
        $h = str_replace ("\r", '', $h);
        if (function_exists ("ereg_replace")) { // deprecation
            $h = @ereg_replace ("/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/", '', $h);
            $h = @ereg_replace ("//[\x20-\x7E]*\n", '', $h);
            $h = @ereg_replace ("#[\x20-\x7E]*\n", '', $h);
            $h = @ereg_replace ("\t|\n", '', $h);
        }     
        return $h;
    }
 
    function html_compress ($h) {
        return preg_replace ('/(?:(?)|(?))(\s+)(?=\<\/?)/',' ', $h);
    }

?>
