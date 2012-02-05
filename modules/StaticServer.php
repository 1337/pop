<?php
    class StaticServer {
        public static $urls = array (
            /*  typically place StaticServer at the end of the list
                of includes to let it serve everything no one else matches.
            */
            "(.)*" => "index",
        );
        
        function index () {
            vars();
        }
    }
?>