<?php
    require_once(MODULE_PATH . 'View.php');

    class Query extends View {
        /*  extends View to get property bags. Don't assign an ID!
            usage:
                get all existing module types as an array of strings:
                    $a = new Query();
                    var_dump($a->found);

                get all objects of a certain type as an array of objects:
                    $a = Pop::obj('Query', 'ModuleName');
                    var_dump($a->get());

                sort by a property:
                    $a = Pop::obj('Query', 'ModuleName');
                    var_dump(
                        $a->filter('id ==', 123)->get(1)
                    );

                sort by many a property:
                    $a = Pop::obj('Query', 'ModuleName');
                    var_dump(
                        $a->filter('id ==', 123)
                          ->filter('age ==', 20)->get(1)
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

        function __construct($module_name = false) {
            // false module name searches all modules.
            $this->found_objects = array(); // init var
            $this->filters = array();
            if ($module_name === false) {
                // search all model types
                $matches = glob(DATA_PATH . '*');
            } elseif (is_string($module_name)) {
                $this->module_name = $module_name;
                // all data are stored as DATA_PATH/class_name/id
                $matches = glob(DATA_PATH . $module_name . DIRECTORY_SEPARATOR . '*');
            } elseif (is_object($module_name)) {
                $module_name = get_class($module_name); // revert to its name
                $this->module_name = $module_name;
                // all data are stored as DATA_PATH/class_name/id
                $matches = glob(DATA_PATH . $module_name . DIRECTORY_SEPARATOR . '*');
            } else {
                $matches = array();
            }
            foreach ((array)$matches as $idx => $match) {
                $this->found[] = basename($match);
            }

            return $this; // chaining for php 5
        }

        public function __call($name, $args) {
            // everyone loves magic functions
            if (substr($name, 0, 7) === 'get_by_') {
                $get_by = substr($name, 7);

                return $this->filter($get_by . ' ==', $args[0]);
            } else if (substr($name, 0, 7) === 'reject_') {
                $reject = substr($name, 7);
                return $this->filter($reject . ' !=', $args[0]);
            }
        }

        public function __toString() {
            // this does something only after get() or fetch() is called.
            if (!is_array($this->found_objects)) {
                throw new Exception("call get() or fetch() first");
            }

            $bfr = '[';
            foreach ($this->found_objects as $obj) {
                $bfr .= (string)$obj;
                $bfr .= ',';
            }
            $bfr .= ']';

            return $bfr;
        }

        public function to_string() {
            // alias
            return $this->__toString();
        }

        public function to_array() {
            // returns array of all object properties.
            $objs = array();
            foreach ((array)$this->found_objects as $obj) {
                $objs[] = $obj->to_array();
            }

            return $objs;
        }

        /**
         * adds a filter to the Query.
         *
         * @param {string} $filter: field name followed by an operator, e.g. 'name =='
         * @param $condition: one of [<, >, ==, !=, <=, >=, IN]
         * @return $this
         */
        public function filter($filter, $condition) {
            $this->filters[] = array($filter, $condition);

            return $this; // chaining for php 5
        }

        /**
         * @param {string} $key: e.g. 'date'
         * @return array
         */
        public function aggregate_by($key) {
            /*  $queryInstance->aggregate_by('date') will return
                [
                    '2013-09-10': [(objects with this date)],
                    '2013-09-11': [(objects with this date)],
                    ...
                ]

                underscore alias: indexBy
            */
            $old_found = $this->found; // keep copy
            $pool = array();

            while ($obj = $this->iterate()) {
                try {
                    $pool[$obj->{$key}][] = $obj;
                } catch (Exception $e) {
                    // first item.
                    $pool[$obj->{$key}] = array($obj);
                }
            }
            $this->found = $old_found; // swap back
            return $pool;
        }

        /**
         * Orders all objects by a key. This is EXTREMELY slow.
         *
         * @param string $by: name of a field
         * @param bool $asc: ascending or descending
         * @return $this
         */
        public function order($by, $asc = true) {
            $this->get();  // loads $this->found_objects

            $this->sort_field = $by;
            usort($t = (array)$this->found_objects,
                  array($this, "_sort_function")); // php automagic
            if (!$asc) {
                $this->found_objects = array_reverse($this->found_objects);
            }

            return $this; // chaining for php 5
        }

        /**
         * fisher-yates shuffle the found variable, NOT found_objects.
         * call before get() or fetch().
         * http://stackoverflow.com/a/6557893/1558430
         *
         * @param bool $strong: whether to use fisher-yates.
         * @return $this
         */
        public function shuffle($strong) {
            if ($strong) {
                for ($i = count($this->found) - 1; $i > 0; $i--) {
                    $j = @mt_rand(0, $i);
                    $tmp = $this->found[$i];
                    $this->found[$i] = $this->found[$j];
                    $this->found[$j] = $tmp;
                }
            } else {
                shuffle($this->found);
            }

            return $this; // chaining for php 5
        }

        /**
         * retrieves objects from the filesystem. use get() to get them.
         *
         * @param int $limit: max number of objects to fetch
         * @return $this
         */
        public function fetch($limit = PHP_INT_MAX) {
            // This class does NOT store or cache these results.
            // calling fetch more than once on the same Query object will reset its list of items found.
            $this->found_objects = array(); // reset var
            $found_count = 0;
            // if the DB has fewer matching results than $limit, this will force
            // fetch() to go through the entire store. Performance hit!!
            // adjust FS_FETCH_HARD_LIMIT.
            foreach ((array)$this->found as $index => $file) {
                $object = $this->_create_object_from_filename($file);
                $include_this_object = $this->_check_against_filters($object);
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
            $this->found = array_map(array($this, '_get_object_name'),
                                     (array)$this->found_objects);

            // reset the filters (doesn't matter no more)
            $object = null;
            $this->filters = array();

            return $this; // chaining
        }

        /**
         * returns query's objects.
         * if fetch() has not been run, it will be run.
         *
         * @param int $limit: max number of objects to fetch
         * @return array
         */
        public function get($limit = PHP_INT_MAX) {
            if (sizeof($this->found_objects) <= 0) {
                $this->fetch($limit); // if nothing, try fetch again just to be sure
            }

            return $this->found_objects;
        }

        public function iterate() {
            // return one result at a time; FALSE for no more rows
            // (same behaviour as mysql_fetch_???)
            if (sizeof($this->found) >= 1) {
                while ($found = array_shift($this->found)) {
                    $object = $this->_create_object_from_filename($found);

                    $include_this_object = $this->_check_against_filters($object);
                    if ($include_this_object) {
                        return $object;
                    }
                    unset ($object);
                }
            }

            return false;
        }

        public function count() {
            /*  Returns the size of the resultset.
                It WILL recount if pending filters are present in the Query object.
            */
            if (sizeof($this->filters) > 0) {
                $this->fetch(); // gotta recount...
            }

            return sizeof($this->found);
        }

        public function pluck($key) {
            // returns an array with only the values of one property
            // from objects fetched.
            $props = array();
            foreach ((array)$this->found_objects as $obj) {
                $props[] = $obj->$key;
            }

            return $props;
        }

        public function min($key) {
            // returns the object by which its $key was the smallest.
            $objs = $this->order($key, true);

            return $objs[0];
        }

        public function max($key) {
            // returns the object by which its $key was the largest.
            $objs = $this->order($key, false);

            return $objs[0];
        }

        private function _sort_function($a, $b) {
            $field = $this->sort_field; // order() supplies this using $by.
            if ($a->{$field} == $b->{$field}) {
                return 0;
            } else {
                return ($a->{$field} < $b->{$field}) ? -1 : 1;
            }
        }

        /**
         * Returns true if object meets filter criteria.
         *
         * @param $object
         * @param $filter: e.g. ['name ==', 'brian']
         * @return bool
         * @throws Exception
         */
        private function _filter_func($object, $filter) {
            $cond = $filter[1]; // e.g. '5'
            if (strpos($filter[0], ' ') !== false) {
                // if space is found in 'name ==', then split by it
                $spl = explode(' ', $filter[0]);
                // >, <, ==, ===, !=, <=, >=, IN, WITHIN, or CONTAINS
                $mode = $spl[1];
                $field = $spl[0];
            } else {
                // else guess by getting last two characters or something
                // >, <, ==, !=, <=, >=, or IN
                $mode = trim(substr($filter[0], -2));
                $field = trim(substr($filter[0], 0,
                                     strlen($filter[0]) - strlen($mode)));
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
                    if (is_string($haystack) && is_string($cond)) {
                        // case-insensitive comparison
                        return (strcasecmp($haystack, $cond) === 0);
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
                    if (is_string($cond)) {
                        return (strpos($cond,
                                       $haystack) >= 0); // 'is found in the condition'
                    } else { // compare as array
                        return (in_array($haystack, $cond));
                    }
                case 'CONTAINS': // reverse IN; this field's value is an array that contains the criterion
                    if (is_string($haystack)) {
                        // 'condition is found in db field'
                        return (strpos($haystack, $cond) >= 0);
                    } else { // compare as array
                        return (in_array($cond, $haystack));
                    }
                default:
                    throw new Exception ("'$mode' is not a recognized filter mode");
                    break;
            }
        }

        /**
         * True if this object meets all filter requirements, False otherwise.
         * @param $object: a Model instance
         * @return bool
         */
        private function _check_against_filters($object) {
            $truth = true;
            foreach ((array)$this->filters as $idx => $filter) {
                // if any filter is not met, $truth is false
                $truth &= $this->_filter_func($object, $filter);
                if (!$truth) {
                    return false;
                }
            }
            return $truth;
        }

        /**
         * @param $o: Model(hello)
         * @return string: Model/hello
         */
        private function _get_object_name($o) {
            // if this is a Model, this will not fail
            return get_class($o) . DIRECTORY_SEPARATOR . $o->id;
        }

        /**
         * @param {string} $file
         * @return Model: object of Id.Type
         */
        private function _create_object_from_filename($file) {
            return Pop::obj($this->module_name, $file);
        }
    }
