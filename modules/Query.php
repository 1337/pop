<?php
    class Query extends Model {
        
        public static $urls = array (
            "lookup/?" => "lookup_ui",
        );

        var $found; // need this to overload $this->found[]

        function __construct ($module_name = false) {
            // false module name searches all modules.
            if (!$module_name) {
                $module_name = '*';
            } elseif (is_object ($module_name)) {
                $module_name = get_class ($module_name); // revert to its name
            }
            // all data are stored as DATA_PATH/id.class_name
            $matches = glob (DATA_PATH . "*.$module_name");
            foreach ((array) $matches as $match) {
                $this->found[] = basename ($match);
            }
        }
    
        function filter () {
            // VERY slow.
        }
        
        function order () {
            // EXTREMELY slow.
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
            return sizeof ($this->found);
        }
        
        function lookup_ui () {
            $q = new Query ('Site');
            $this->render (null, array (
                'content' => var_export ($q->found, true)
            ));
        }
    }
?>