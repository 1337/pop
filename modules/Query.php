<?php
    require_once (MODULE_PATH . 'Model.php');

    class Query {
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
                        $a->filter('id ==', 123)->get (1)
                    );
            
            reusing a Query object has unexpected results.
            always create a new Query object.
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
            $this->filters = array ();
            if ($module_name === false) {
                // search all model types
                $matches = glob (DATA_PATH . "*");
            } elseif (is_string ($module_name)) {
                $this->module_name = $module_name;
                // all data are stored as DATA_PATH/class_name/id
                $matches = glob (DATA_PATH . "$module_name/*");
            } elseif (is_object ($module_name)) {
                $module_name = get_class ($module_name); // revert to its name
                $this->module_name = $module_name;
                // all data are stored as DATA_PATH/class_name/id
                $matches = glob (DATA_PATH . "$module_name/*");
            } else {
                $matches = array ();
            }
            foreach ((array) $matches as $match) {
                $this->found[] = basename ($match);
            }
            return $this; // chaining for php 5
        }
    
        public function filter ($filter, $condition) {
            // adds a filter to the Query.
            // $filter = field name followed by an operator, e.g. 'name =='
            // comparison operators allowed: <, >, ==, !=, <=, >=, IN
            
            $this->filters[] = array ($filter, $condition);
            return $this; // chaining for php 5
        }
        
        public function order ($by, $asc = true) {
            // EXTREMELY slow.
            $this->sort_field = $by;
            usort ($t = (array) $this->found_objects, array ($this, "_sort_function")); // php automagic
            if (!$asc) {
                $this->found_objects = array_reverse ($this->found_objects);
            }
            return $this; // chaining for php 5
        }

        public function fetch ($limit = PHP_INT_MAX) {
            // This class does NOT store or cache these results.
            // calling fetch more than once on the same Query object will reset its list of items found.
            $this->found_objects = array (); // reset var
            $i = $found_count = 0;
            // if the DB has fewer matching results than $limit, this will force
            // fetch() to go through the entire store. Performance hit!!
            // adjust FS_FETCH_HARD_LIMIT.
            foreach ((array) $this->found as $index => $file) {
                $object = $this->_create_object_from_filename ($file);
                $include_this_object = true;
                foreach ((array) $this->filters as $filter) {
                    // if any filter is not met, $include_this_object is false
                    $include_this_object &= $this->_filter_function ($object, $filter);
                }
                if ($include_this_object) {
                    $this->found_objects[] = $object;
                    ++$found_count;
                }
                if ($found_count >= $limit) {
                    // we have enough objects! quit looking immediately.
                    break;
                }
                if ($index > FS_FETCH_HARD_LIMIT) {
                    break; // when should Query just give up?
                }
            }
            // update filenames (count() uses it)
            $this->found = array_map (array ($this, "_get_object_name"), (array) $this->found_objects);
            
            // reset the filters (doesn't matter no more)
            $object = null;
            $this->filters = array ();
            return $this;
        }

        public function get ($limit = PHP_INT_MAX) {
            // throw objects out.
            if (sizeof ($this->found_objects) <= 0) {
                $this->fetch ($limit); // if nothing, try fetch again just to be sure
            }
            return $this->found_objects;
        }
        
        public function iterate () {
            // return one result at a time; FALSE for no more rows
            // (same behaviour as mysql_fetch_???)
            if ($this->found) {
                while ($found = array_shift ($this->found)) {
                    $object = $this->_create_object_from_filename ($found);
                    
                    // same code from fetch ()
                    $include_this_object = true;
                    foreach ((array) $this->filters as $filter) {
                        // if any filter is not met, $include_this_object is false
                        $include_this_object &= $this->_filter_function ($object, $filter);
                    }
                    // $include_this_object = array_reduce ($this->filters, array ($this, '_filter_function'), true);
                    if ($include_this_object) {
                        return $object;
                    }
                    unset ($object);
                }
            }
            return false;
        }
        
        public function count () {
            /*  Returns the size of the resultset.
                It WILL recount if pending filters are present in the Query object.
            */
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
            if (strpos ($filter[0], ' ') !== false) {
                // if space is found in 'name ==', then split by it
                $spl = explode (' ', $filter[0]);
                $mode = $spl[1]; // should be >, <, ==, !=, <=, >=, or IN
                $field = $spl[0];
            } else {
                // else guess by getting last two characters or something
                $mode = trim (substr ($filter[0], -2)); // should be >, <, ==, !=, <=, >=, or IN
                $field = trim (substr ($filter[0], 0, strlen ($filter[0]) - strlen ($mode)));
            }
            $haystack = $object->{$field}; // good name
            switch ($mode) {
                case '>':
                    return ($haystack > $cond);
                case '>=':
                    return ($haystack >= $cond);
                case '<':
                    return ($haystack < $cond);
                case '<=':
                    return ($haystack <= $cond);

                case '=':
                case '==':
                    if (is_string ($haystack) && is_string ($cond)) {
                        // case-insensitive comparison
                        return strcasecmp ($haystack, $cond);
                    } else {
                        return ($haystack == $cond);
                    }
                case '===':
                    return ($haystack === $cond);
                
                case '!=':
                    return ($haystack != $cond);
                case 'WITHIN': // within; $cond must be [min, max]
                    return ($haystack >= $cond[0] && $haystack <= $cond[1]);
                case 'IN': // list of criteria supplied contains this field's value
                    if (is_string ($cond)) {
                        return (strpos ($cond, $haystack) >= 0); // 'is found in the condition'
                    } else { // compare as array
                        return (in_array ($haystack, $cond));
                    }
                case 'CONTAINS': // reverse IN; this field's value is an array that contains the criterion
                    if (is_string ($haystack)) {
                        return (strpos ($haystack, $cond) >= 0); // 'condition is found in db field'
                    } else { // compare as array
                        return (in_array ($cond, $haystack));
                    }
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
            if (function_exists ('new_object')) {
                return new_object ($file, $this->module_name);
            } else {
                return new $this->module_name ($file); // new TYPE (FILENAME)        
            }
        }
    }
    
?>
