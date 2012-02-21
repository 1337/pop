<?php
    class Query extends Model {
    // extends Model to get property bags. Don't assign an ID!
        
        // array of matching filenames.
        var $found; // need this to overload $this->found[]

        // array of objects successfully queried. calling fetch() clears it.
        var $found_objects;

        function __construct ($module_name = false) {
            // false module name searches all modules.
            $this->found_objects = array (); // init var
            if (!$module_name) {
                $module_name = '*';
            } elseif (is_object ($module_name)) {
                $module_name = get_class ($module_name); // revert to its name
            }
            // all data are stored as DATA_PATH/class_name/id
            $matches = glob (DATA_PATH . "$module_name/*");
            foreach ((array) $matches as $match) {
                $this->found[] = basename ($match);
            }
            return $this; // chaining for php 5
        }
        
        function all () {
            $this->get();
            return $this; // chaining for php 5
        }
    
        function filter ($filter, $condition) {
            // run fetch() before filter. since all() calls fetch(), you can do that too.
            // $filter = field name followed by an operator, e.g. 'name =='
            // comparison operators allowed: <, >, ==, !=, <=, >=, IN
            $this->filter = $filter;
            $this->filter_condition = $condition;
            $this->found_objects = array_filter ($t = (array) $this->found_objects, array ($this, "_filter_function"));
            
            // update filename counts (count() uses it)
            $this->found = array_map (array ($this, "_get_object_name"), (array) $this->found_objects);
            return $this; // chaining for php 5
        }
        
        function order ($by, $asc = true) {
            // EXTREMELY slow.
            $this->sort_field = $by;
            usort ($t = (array) $this->found_objects, array ($this, "_sort_function")); // php automagic
            if (!$asc) {
                $this->found_objects = array_reverse ($this->found_objects);
            }
            return $this; // chaining for php 5
        }

        function fetch ($limit = PHP_INT_MAX) {
            // This class does NOT store or cache these results.
            // calling fetch more than once on the same object will erase them.
            $files = array_slice ((array) $this->found, 0, $limit);
            $this->found_objects = array (); // reset var
            foreach ((array) $this->found as $file) {
                $this->found_objects[] = $this->_create_object_from_filename ($file);
            }
            // return $this->found_objects;
            return $this;
        }

        function get () {
            // throw objects out.
            if (sizeof ($this->found_objects) <= 0) {
                $this->fetch (); // if nothing, try fetch again just to be sure
            }
            return $this->found_objects;
        }
        
        function count () {
            // fast enough
            return sizeof ($this->found);
        }

        private function _sort_function ($a, $b) {
            $field = $this->sort_field; // order() supplies this using $by.
            if ($a->{$field} == $b->{$field}) {
                return 0;
            } else {
                return ($a->{$field} < $b->{$field}) ? -1 : 1;
            }
        }
        
        private function _filter_function ($a) {
            $filter = $this->filter; // e.g. 'name =='
            $cond = $this->filter_condition; // e.g. '5'
            $mode = trim (substr ($filter, -2)); // should be >, <, ==, !=, <=, >=, or IN
            $field = trim (substr ($filter, 0, strlen ($filter) - strlen ($mode)));
            switch ($mode) {
                case '>':
                    return ($a->{$field} > $cond);
                case '<':
                    return ($a->{$field} < $cond);
                case '==':
                    return ($a->{$field} == $cond);
                case '!=':
                case '<>': // because vb.
                    return ($a->{$field} != $cond);
                case '>=':
                    return ($a->{$field} >= $cond);
                case '<=':
                    return ($a->{$field} <= $cond);
                case '><': // within; $cond must be [min, max]
                    return ($a->{$field} >= $cond[0] && $a->{$field} <= $cond[1]);
                case 'IN':
                    return (in_array ($a->{$field}, $cond));
                default:
                    throw new Exception ("'$mode' is not a recognized filter mode");
                    break;
            }
        }
        
        private function _get_object_name ($o) {
            // Model(hello) -> Model/hello
            return get_class ($o) . '/' . $o->id; // if this is a Model, this will not fail
        }
        
        private function _create_object_from_filename ($file) {
            // output: object of Id.Type
            if (!strpos ($file, '.')) {
                // this can't possibly be an existing object.
                throw new Exception ('Illegal string: ' . $file);
            } else {
                $object_type = end (explode ('.', $file)); // filename.TYPE
                $object_name = basename ($file, ".$object_type"); // FILENAME.type
                return new $object_type ($object_name); // new TYPE (FILENAME)        
            }
        }
    }
?>
