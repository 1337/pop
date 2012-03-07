<?php
    require_once (dirname (__FILE__) . '/Model.php');

    class Compressor {
        // compression functions
        
        private static function css_compress ($h) {
            /* remove comments */
            $h = preg_replace ('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $h);
            /* remove tabs, spaces, newlines, etc. */
            $h = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $h);
         
            return $h;
        }

        public static function js_compress ($h) {
            /* remove comments */
            $h = preg_replace ('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $h);
         
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
                header ('Content-type: text/css');
                header ('Cache-Control: max-age=37739520, public');
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . "css/$file.css");
                    echo $this->css_compress (file_get_contents ($filename));
                }
            }
        }
        
        function js () {
            $files = vars('files', vars('file', false));
            if ($files !== false) {
                header ('Content-type: text/javascript; charset: UTF-8');
                header ('Cache-Control: max-age=37739520, public');
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . "js/$file.js");
                    // JS compressor adds a ';' at the end of each script by default
                    echo $this->js_compress (file_get_contents ($filename) . ';');
                }
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
