<?php
    class Compressor extends Model {
        public static $urls = array (
            "c/?" => "css",
            "j/?" => "js",
        );
        
        function css () {
            $file = vars('file', false);
            if ($file !== false) {
                $filename = $this->safe_file_name (TEMPLATE_PATH . "$file.css");
                ob_start ("ob_gzhandler");
                header ('Content-type: text/css');
                header ('Cache-Control: max-age=37739520, public');
                echo css_compress (file_get_contents ($filename));
            }
        }
        
        function js () {
            $file = vars('file', false);
            if ($file !== false) {
                $filename = $this->safe_file_name (TEMPLATE_PATH . "$file.js");
                ob_start ("ob_gzhandler");
                header ('Content-type: text/javascript; charset: UTF-8');
                header ('Cache-Control: max-age=37739520, public');
                echo file_get_contents ($filename);
            }
        }
        
        function safe_file_name ($n) {
            // well, rejects traversal.
            if (strpos ($n, '..') !== false || 
                strpos ($n, '//') !== false) {
                throw new Exception ('file name unsafe!');
            } else {
                return $n;
            }
        }
    }
?>