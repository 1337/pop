<?php
    require_once (dirname (__FILE__) . '/Model.php');

    class StaticServer {
        public static $urls = array (
            /*  typically place StaticServer at the end of the list
                of includes to let it serve everything no one else matches.
            */
            "viewer/?" => "object_viewer",
            "static/?" => "html_loader",
            
            
            "(.)*" => "index",
            // NO more rules under (.)* !
        );
        
        function index () {
            // var_dump ($_GET);
            $url = $_SERVER['REQUEST_URI'];
            $req = trim (substr ($url, strpos ($url, STATIC_PATH) + strlen (STATIC_PATH)), '/?');
            $filename = DATA_PATH . STATIC_PATH . $req;
            if (file_exists ($filename)) {
                echo (file_get_contents ($filename));
            } else {
                throw new Exception("404 not found $filename");
            }
        }
        
        function object_viewer () {
            $id = vars ('id');
            $class = vars ('class');
            header ('Content-type: text/plain');
            print_r (new_object ($id, $class));
        }
        
        function html_loader () {
            
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
