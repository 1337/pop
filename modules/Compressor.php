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

        public static function html_compress ($h) {
            return preg_replace ('/(?:(?)|(?))(\s+)(?=\<\/?)/',' ', $h);
        }

        function css () {
            $files = vars ('files', vars('file', false));
            if ($files !== false) {
                @ob_start ();
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . 'css/' . $file . '.css');
                    echo $this->css_compress (file_get_contents ($filename));
                }
                header ('Content-type: text/css');
                header ('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+10 years')) . ' GMT');
            }
        }
        
        function js () {
            $files = vars('files', vars('file', false));
            if ($files !== false) {
                @ob_start ();
                foreach (explode (',', $files) as $file) {
                    $filename = $this->safe_file_name (VIEWS_PATH . 'js/' . $file . '.js');
                    // JS compressor adds a ';' at the end of each script by default
                    echo str_replace (
                        array ('<!-- domain -->', '<!-- subdir -->'),
                        array (      DOMAIN,            SUBDIR     ),
                        $this->js_compress (file_get_contents ($filename) . ';')
                    );
                }
                header ('Content-type: text/javascript; charset: UTF-8');
                header ('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+10 years')) . ' GMT');
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
