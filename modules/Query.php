<?php
    class Query {
        
        var $found;
        
        function __construct ($module_name = false) {
            // false module name searches all modules.
            if (!$module_name) {
                $module_name = '*';
            }
            $this->found = glob (DATA_PATH . "*.$module_name");
        }
    
        function filter () {
            // VERY slow.
        }
        
        function order () {
            // VERY slow.
        }
        
        function get () {
            foreach ((array) $this->found as $file) {
                $object_type = end (explode ('.', $file)); // filename.TYPE
                $object_name = basename ($file, ".$object_type"); // FILENAME.type
                $objects[] = new $object_type ($object_name); // new TYPE (FILENAME)
            }
        }
        
        function count () {
            // fast enough
        }
    }
?>
