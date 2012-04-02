<?php
    require_once (MODULE_PATH . 'Model.php');

    class Compressor {
        // compression functions
        
        private static function css_compress ($h) {
            /* remove comments */
            if (TEMPLATE_COMPRESS === true) {
                $h = preg_replace ('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $h);
                /* remove tabs, spaces, newlines, etc. */
                $h = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $h);
            }
            return $h;
        }

        public static function js_compress ($h) {
            /* remove comments */
            if (TEMPLATE_COMPRESS === true) {
                $h = preg_replace ('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $h);
            }
            return $h;
        }

        public static function php_compress ($h) {
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

        public static function html_compress ($h) {
            return preg_replace ('/(?:(?)|(?))(\s+)(?=\<\/?)/',' ', $h);
        }

        function css () {
            $files = vars('files', vars('file', false));
            if ($files !== false) {
                @ob_start ();
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . "css/$file.css");
                    echo $this->css_compress (file_get_contents ($filename));
                }
                // $fc = ob_get_contents ();
                // $etag = create_etag ($_SERVER['REQUEST_URI']);
                // @file_put_contents (CACHE_PATH . $etag, $fc);
                header ('Content-type: text/css');
                header ('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+10 years')) . ' GMT');
                // header ('ETag: "' . $etag . '"');
            }
        }
        
        function js () {
            $files = vars('files', vars('file', false));
            if ($files !== false) {
                @ob_start ();
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . "js/$file.js");
                    // JS compressor adds a ';' at the end of each script by default
                    echo str_replace (
                        array ('<!-- domain -->', '<!-- subdir -->'),
                        array (      DOMAIN,            SUBDIR     ),
                        $this->js_compress (file_get_contents ($filename) . ';')
                    );
                }
                // $fc = ob_get_contents ();
                // $etag = create_etag ($_SERVER['REQUEST_URI']);
                // @file_put_contents (CACHE_PATH . $etag, $fc);
                header ('Content-type: text/javascript; charset: UTF-8');
                header ('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+10 years')) . ' GMT');
                // header ('ETag: "' . $etag . '"');
            }
        }
        
        private function safe_file_name ($n) {
            // well, rejects traversal.
            if (strpos ($n, '..') !== false || // traversal (../../)
                strpos ($n, '//') !== false || // remote (http://)
                strpos ($n, '~') === 0) { // traversal (~/...)
                throw new Exception ('file name unsafe!');
            } else {
                return $n;
            }
        }
    }
?>
