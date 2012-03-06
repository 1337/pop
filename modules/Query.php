<?php
    require_once (dirname (__FILE__) . '/Model.php');

    class Query extends Model {
        /*  extends Model to get property bags. Don't assign an ID!
            usage:
                get all existing module types as an array of strings: 
                    $a = new Query();
                    var_dump ($a->found);
                
                get all objects of a certain type as an array of objects:
                    $a = new_object ('ModuleName', 'Query');
                    var_dump ($a->get ());
                
                sort by a property:
                    $a = new_object ('ModuleName', 'Query');
                    var_dump (
                        $a->filter('id ==', 123)
                          ->get ()
                    );
        */
        
        var $found; // public array of matching filenames. need this to overload $this->found[]
        protected $found_objects; // array of objects successfully queried. calling fetch() clears it.
        
        // module name, e.g. "Product", "Student"
        protected $module_name;
        
        /*  an array of filters: 
            [
                ['name ==', 'brian'],
                ['age >=', '1337']
            ]
        */
        protected $filters;

        function __construct ($module_name = false) {
            // false module name searches all modules.
            $this->found_objects = array (); // init var
            if (!$module_name) {
                $module_name = '*'; // <-- gives you module types
            } elseif (is_object ($module_name)) {
                $module_name = get_class ($module_name); // revert to its name
            }
            $this->module_name = $module_name;
            // all data are stored as DATA_PATH/class_name/id
            $matches = glob (DATA_PATH . "$module_name/*");
            foreach ((array) $matches as $match) {
                $this->found[] = basename ($match);
            }
            return $this; // chaining for php 5
        }
    
        function filter ($filter, $condition) {
            // adds a filter to the Query.
            // $filter = field name followed by an operator, e.g. 'name =='
            // comparison operators allowed: <, >, ==, !=, <=, >=, IN
            
            // $this->filter_condition = $condition;

            $this->filters[] = array ($filter, $condition);
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
            // calling fetch more than once on the same Query object will reset its list of items found.
            // $files = array_slice ((array) $this->found, 0, $limit);
            $this->found_objects = array (); // reset var
            $found_count = 0;
            foreach ((array) $this->found as $file) {
                $object = $this->_create_object_from_filename ($file);
                $include_this_object = true;
                foreach ((array) $this->filters as $filter) {
                    // if any filter is not met, $include_this_object is false
                    $include_this_object = $include_this_object && 
                                           $this->_filter_function ($object, $filter);
                    unset ($this->filter);
                }
                if ($include_this_object) {
                    $this->found_objects[] = $object;
                    $found_count++;
                }
                if ($found_count >= $limit) {
                    // we have enough objects! quit looking immediately.
                    break;
                }
            }
            // update filenames (count() uses it)
            $this->found = array_map (array ($this, "_get_object_name"), (array) $this->found_objects);
            
            // reset the filters (doesn't matter no more)
            unset ($this->filters);
            // return $this->found_objects;
            return $this;
        }

        function get ($limit = PHP_INT_MAX) {
            // throw objects out.
            
            /* foreach ((array) $this->found as $file) {
                $this->found_objects[] = $this->_create_object_from_filename ($file);
            }
            $this->found_objects = array_filter ($t = (array) $this->found_objects, array ($this, "_filter_function")); */
            
            if (sizeof ($this->found_objects) <= 0) {
                $this->fetch ($limit); // if nothing, try fetch again just to be sure
            }
            return $this->found_objects;
        }
        
        function count () {
            // fast enough
            if (sizeof ($this->filters) > 0) {
                $this->fetch (); // gotta recount...
            }
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
        
        private function _filter_function ($object, $filter) {
            // $filter = e.g. ['name ==', 'brian']
            // returns true if object meets filter criteria.
            $cond = $filter[1]; // e.g. '5'
            $mode = trim (substr ($filter[0], -2)); // should be >, <, ==, !=, <=, >=, or IN
            $field = trim (substr ($filter[0], 0, strlen ($filter[0]) - strlen ($mode)));
            $haystack = $object->{$field}; // good name
            switch ($mode) {
                case '>':
                    return ($haystack > $cond);
                case '<':
                    return ($haystack < $cond);
                
                case '=':
                case '==':
                    return ($haystack == $cond);
                case '!=':
                case '<>': // because vb.
                    return ($haystack != $cond);
                case '>=':
                    return ($haystack >= $cond);
                case '<=':
                    return ($haystack <= $cond);
                case '><': // within; $cond must be [min, max]
                    return ($haystack >= $cond[0] && $haystack <= $cond[1]);
                case 'IN':
                    return (in_array ($haystack, $cond));
                case 'NI': // reverse IN
                    return (in_array ($cond, $haystack));
                case '!%': // 'is within the condition'
                    return (strpos ($cond, $haystack) >= 0);
                case '%': // 'contains condition'
                    return (strpos ($haystack, $cond) >= 0);
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
            return new $this->module_name ($file); // new TYPE (FILENAME)        
        }
    }
?>
