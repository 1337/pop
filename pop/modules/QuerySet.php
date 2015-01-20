<?php

namespace Pop;

/*  extends View to get property bags. Don't assign an ID!
    usage:
        get all existing module types as an array of strings:
            $a = new QuerySet();
            var_dump($a->found);

        get all objects of a certain type as an array of objects:
            $a = Pop::obj('QuerySet', 'ModuleName');
            var_dump($a->get());

        sort by a property:
            $a = Pop::obj('QuerySet', 'ModuleName');
            var_dump(
                $a->filter('id ==', 123)->get(1)
            );

        sort by many a property:
            $a = Pop::obj('QuerySet', 'ModuleName');
            var_dump(
                $a->filter('id ==', 123)
                  ->filter('agQuerye ==', 20)->get(1)
            );

    reusing a QuerySet object has unexpected results.
    always create a new QuerySet object.
*/
class QuerySet implements QuerySetInterface, \Iterator {

//    var $found; // public array of matching filenames. need this to overload $this->found[]
//    protected $foundObjects; // array of objects successfully queried. calling fetch() clears it.

    // module name, e.g. "Product", "Student"
    protected $moduleName;

    // QuerySet implements Iterator.
    // See http://php.net/manual/en/class.iterator.php
    protected $position = 0;

    /*  an array of filters:
        [
            ['name ==', 'brian'],
            ['age >=', '1337']
        ]
    */
    protected $filters;

    /**
     * @param string  $moduleName   type of model to filter.
     * @param array   $filters      filters to construct.
     */
    function __construct($moduleName, $filters=[]) {
        // init vars
        $this->foundObjects = [];
        $this->position = 0;
        $this->filters = $filters;

        if (is_string($moduleName)) {
            $this->moduleName = $moduleName;
            // all data are stored as DATA_PATH/class_name/id
            $matches = glob(DATA_PATH . $moduleName . DIRECTORY_SEPARATOR . '*');
        } elseif (is_object($moduleName)) {
            $moduleName = get_class($moduleName); // revert to its name
            $this->moduleName = $moduleName;
            // all data are stored as DATA_PATH/class_name/id
            $matches = glob(DATA_PATH . $moduleName . DIRECTORY_SEPARATOR . '*');
        } else {
            $matches = [];
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
        }
        return $this;
    }


    public function __toString() {

        $bfr = '[';
        $objects = (array)$this->foundObjects;
        foreach ($objects as $obj) {
            $bfr .= (string)$obj;
            $bfr .= ',';
        }

        // remove trailing comma, if it exists
        if (count($objects) > 0) {
            $bfr = substr($bfr, -1, 0);
        }
        $bfr .= ']';

        return $bfr;
    }

    public function toArray() {
        // returns array of all object _properties.
        $objs = [];
        foreach ((array)$this->foundObjects as $obj) {
            $objs[] = $obj->toArray();
        }

        return $objs;
    }

    public function filter($filter, $condition) {
        $this->filters[] = [$filter, $condition];

        return $this;  // chaining for php 5
    }

    /**
     * @param {string} $key: e.g. 'date'
     * @return array
     */
    public function aggregate($key) {
        /*  $queryInstance->aggregate('date') will return
            [
                '2013-09-10': [(objects with this date)],
                '2013-09-11': [(objects with this date)],
                ...
            ]

            underscore alias: indexBy
        */
        /*$old_found = $this->found; // keep copy
        $pool = [];

        while ($obj = $this->iterate()) {
            try {
                $pool[$obj->{$key}][] = $obj;
            } catch (\Exception $e) {
                // first item.
                $pool[$obj->{$key}] = [$obj];
            }
        }
        $this->found = $old_found; // swap back
        return $pool;*/
    }

    /**
     * Orders all objects by a key. This is EXTREMELY slow.
     *
     * @param string $by: name of a field
     * @param bool $asc: ascending or descending
     * @return $this
     */
    public function orderBy($by, $asc=true) {
        $this->get();  // loads $this->foundObjects

        $this->sort_field = $by;

        // php automagic callbacks
        usort($this->foundObjects, [$this, "_sortFunction"]);
        if (!$asc) {
            $this->foundObjects = array_reverse($this->foundObjects);
        }

        return $this; // chaining for php 5
    }

    /**
     * fisher-yates shuffle the found variable, NOT foundObjects.
     * call before get() or fetch().
     * http://stackoverflow.com/a/6557893/1558430
     *
     * @param bool $strong: whether to use fisher-yates.
     * @return $this
     */
    public function shuffle($strong) {
        if (!$strong) {
            shuffle($this->found);
            return $this;
        }

        for ($i = count($this->found) - 1; $i > 0; $i--) {
            $j = @mt_rand(0, $i);
            $tmp = $this->found[$i];
            $this->found[$i] = $this->found[$j];
            $this->found[$j] = $tmp;
        }
        return $this;
    }

    /**
     * retrieves objects from the filesystem. use get() to get them.
     *
     * @param int $limit: max number of objects to fetch
     * @return $this
     */
    public function fetch($limit=PHP_INT_MAX) {
        // This class does NOT store or cache these results.
        // calling fetch more than once on the same QuerySet object will reset its list of items found.
        $this->foundObjects = []; // reset var
        $found_count = 0;
        // if the DB has fewer matching results than $limit, this will force
        // fetch() to go through the entire store. Performance hit!!
        // adjust FS_FETCH_HARD_LIMIT.

        /*
        foreach ((array)$this->found as $index => $file) {
            $object = $this->_create_object_from_filename($file);
            $include_this_object = $this->_check_against_filters($object);
            if ($include_this_object) {
                $this->foundObjects[] = $object;
                ++$found_count;
            }
            if ($found_count >= $limit) {
                // we have enough objects! quit looking immediately.
                break;
            }
            if ($index > FS_FETCH_HARD_LIMIT) {
                break; // when should QuerySet just give up?
            }
        }
        */
        // reset the filters (doesn't matter no more)
        $object = null;
        $this->filters = [];

        return $this; // chaining
    }

    /**
     * returns query's objects.
     * if fetch() has not been run, it will be run.
     *
     * @param int $limit: max number of objects to fetch
     * @return array
     */
    public function get($limit=PHP_INT_MAX) {
        if (count($this->foundObjects) <= 0) {
            $this->fetch($limit); // if nothing, try fetch again just to be sure
        }

        return $this->foundObjects;
    }

    /**
     * @deprecated
     */
    public function iterate() {
        // return one result at a time; FALSE for no more rows
        // (same behaviour as mysql_fetch_???)
        /*if (count($this->found) >= 1) {
            while ($found = array_shift($this->found)) {
                $object = $this->_create_object_from_filename($found);

                $include_this_object = $this->_check_against_filters($object);
                if ($include_this_object) {
                    return $object;
                }
                unset ($object);
            }
        }*/

        return false;
    }

    public function count() {
        /*  Returns the size of the resultset.
            It WILL recount if pending filters are present in the QuerySet object.
        */
        if (count($this->filters) > 0) {
            $this->fetch(); // gotta recount...
        }

        return count($this->found);
    }

    public function pluck($key) {
        // returns an array with only the values of one property
        // from objects fetched.
        $props = [];
        foreach ((array)$this->foundObjects as $obj) {
            $props[] = $obj->$key;
        }

        return $props;
    }

    public function first() {
        return $this->foundObjects[0];
    }

    public function last() {
        return end($this->foundObjects);
    }

    public function delete() {
        // objects could never be deleted. whoops.
        foreach ($this->foundObjects as $obj) {
            unlink($obj->_path());
        }
    }

    private function _sortFunction($a, $b) {
        $field = $this->sort_field; // orderBy() supplies this using $key.
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
     * @throws \Exception
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
            case '>';
            case 'GT';
                return ($haystack > $cond);
            case '>=':
            case 'GTE';
                return ($haystack >= $cond);
            case '<';
            case 'LT';
                return ($haystack < $cond);
            case '<=';
            case 'LTE';
                return ($haystack <= $cond);
            case '=':
            case '==':
            case 'EQV';
                if (is_string($haystack) && is_string($cond)) {
                    // case-insensitive comparison
                    return (strcasecmp($haystack, $cond) === 0);
                } else {
                    return ($haystack == $cond);
                }
            case '===';
            case 'EQ';
                return ($haystack === $cond);
            case '!=';
            case 'NEQ';
                return ($haystack != $cond);
            case 'WITHIN'; // within; $cond must be [min, max]
            case 'RANGE';
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
                throw new \Exception("'$mode' is not a recognized filter mode");
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
        // return Pop::obj($this->moduleName, $file);
    }











    /**
     * Return the current element
     * @return mixed Can return any type.
     */
    public function current() {
        // return one result at a time; FALSE for no more rows
        // (same behaviour as mysql_fetch_???)


        // @stub
        return $this->foundObjects[$this->position];

        if (count($this->found) >= 1) {
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

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next() {
        ++$this->position;
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return isset($this->foundObjects[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->position = 0;
    }
}
