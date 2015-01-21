<?php

namespace Pop;

/**
 * QuerySet implements Iterator.
 * See http://php.net/manual/en/class.iterator.php
 *
 * Reusing a QuerySet object has unexpected results.
 * Always create a new QuerySet object.
 */
class QuerySet implements QuerySetInterface, \Iterator {

    // module name, e.g. "Product", "Student"
    protected $_moduleName;
    protected $_filenames;          // array of _filenames for this module name.
    protected $_objects = [];       // array of cached _objects already deserialized
    protected $_position = 0;       // index for the above
    protected $_filters = [];       // array of _filters: [ ['name ==', 'brian'], ['age >=', '1337'] ]
    protected $_orderField = 'id';  // the key to order by.

    /**
     * init vars
     *
     * @param string $moduleName      type of model to query.
     * @param array  $filters         filters to construct.
     * @param string $orderField      the key to order by.
     * @throws \Exception
     */
    public function __construct($moduleName, $filters=[], $orderField='id') {
        $this->_filters = $filters;
        $this->_orderField = $orderField;

        if (is_object($moduleName)) {
            $moduleName = get_class($moduleName); // revert to its name
        }
        if (!is_string($moduleName)) {
            throw new \Exception('moduleName must be a string; got' .
                                 gettype($moduleName));
        }
        $this->_moduleName = $moduleName;
        $this->_filenames = self::_findModuleFiles($moduleName);

        return $this; // chaining for php 5
    }

    public function __call($name, $args) {
        // everyone loves magic functions
        /*if (substr($name, 0, 7) === 'get_by_') {
            $get_by = substr($name, 7);

            return $this->filter($get_by . ' ==', $args[0]);
        }*/
        return $this;
    }

    public function __toString() {
        $bfr = '[';
        $objects = $this->toArray();
        foreach ($objects as $obj) {
            $bfr .= (string)$obj;
            $bfr .= ',';
        }

        if (count($objects) > 0) {  // remove trailing comma, if it exists
            $bfr = substr($bfr, -1, 0);
        }
        $bfr .= ']';

        return $bfr;
    }

    /**
     * @return array   of all objects in JSON format.
     */
    public function toArray() {
        $objects = [];
        foreach ((array)$this->_objects as $obj) {
            if (method_exists($obj, 'toArray')) {
                $objects[] = $obj->toArray();
            } else {
                $objects[] = (array)$obj;
            }
        }

        return $objects;
    }

    /**
     * @param $filter      string    e.g. 'id >='
     * @param $condition   string    e.g. '1337'
     * @return $this
     */
    public function filter($filter, $condition) {
        $this->_filters[] = [$filter, $condition];

        return $this;
    }

    /**
     * @TODO
     * @param {string} $key: e.g. 'date'
     * @return array
     */
    public function aggregate($key) {
        /*  $queryInstance->aggregate('date') will return
            [
                '2013-09-10': [(_objects with this date)],
                '2013-09-11': [(_objects with this date)],
                ...
            ]

            underscore alias: indexBy
        */
        /*$old_found = $this->_filenames; // keep copy
        $pool = [];

        while ($obj = $this->iterate()) {
            try {
                $pool[$obj->{$key}][] = $obj;
            } catch (\Exception $e) {
                // first item.
                $pool[$obj->{$key}] = [$obj];
            }
        }
        $this->_filenames = $old_found; // swap back
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
        $this->get();  // loads $this->_objects

        $this->_orderField = $by;

        // php automagic callbacks
        usort($this->_objects, [$this, "_sortFunction"]);
        if (!$asc) {
            $this->_objects = array_reverse($this->_objects);
        }

        return $this; // chaining for php 5
    }

    /**
     * fisher-yates shuffle the found variable, NOT _objects.
     * call before get() or fetch().
     * http://stackoverflow.com/a/6557893/1558430
     *
     * @param bool $strong: whether to use fisher-yates.
     * @return $this
     */
    public function shuffle($strong) {
        if (!$strong) {
            shuffle($this->_filenames);
            return $this;
        }

        for ($i = count($this->_filenames) - 1; $i > 0; $i--) {
            $j = @mt_rand(0, $i);
            $tmp = $this->_filenames[$i];
            $this->_filenames[$i] = $this->_filenames[$j];
            $this->_filenames[$j] = $tmp;
        }
        return $this;
    }

    /**
     * retrieves _objects from the filesystem. use get() to get them.
     * @return $this
     */
    public function fetch() {
        // This class does NOT store or cache these results.
        // calling fetch more than once on the same QuerySet object will reset its list of items found.
        $this->_objects = []; // reset var
        $found_count = 0;
        // if the DB has fewer matching results than $limit, this will force
        // fetch() to go through the entire store. Performance hit!!
        // adjust FS_FETCH_HARD_LIMIT.

        /*
        foreach ((array)$this->_filenames as $index => $file) {
            $object = $this->_restoreObject($file);
            $include_this_object = $this->_checkAgainstFilters($object);
            if ($include_this_object) {
                $this->_objects[] = $object;
                ++$found_count;
            }
            if ($found_count >= $limit) {
                // we have enough _objects! quit looking immediately.
                break;
            }
            if ($index > FS_FETCH_HARD_LIMIT) {
                break; // when should QuerySet just give up?
            }
        }
        */
        // reset the _filters (doesn't matter no more)
        $object = null;
        $this->_filters = [];

        return $this->_clone(); // chaining
    }

    /**
     * load itself as an iterator, copying every object into an array.
     * index will be set to 0 like it should be.
     *
     * @return array      a copy of query's objects.
     */
    public function get() {
        $objects = iterator_to_array($this);
        $this->rewind();
        return $objects;
    }

    /**
     * @return int       number of objects matching the query.
     */
    public function count() {
        if (count($this->_filters) === 0) {
            return count($this->_filenames);
        }

        // create a copy of itself, make it fetch itself, and return its count.
        $qsCopy = $this->_clone();
        $qsCopy->fetch();
        return count($qsCopy->_objects);
    }

    /**
     * Deletes all objects from the filesystem.
     * @throws \Exception
     */
    public function delete() {
        foreach ($this->_objects as $obj) {
            if (!method_exists($obj, '_path')) {
                throw new \Exception("$obj has no _path!");
            }
            lib\warnings\startCatchWarnings();
            try {
                unlink($obj->_path());
            } catch (\Exception $err) {
                lib\warnings\endCatchWarnings();
                throw;
            }
            lib\warnings\endCatchWarnings();
        }
    }

    private function _sortFunction($a, $b) {
        $field = $this->_orderField; // orderBy() supplies this using $key.
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
     * @param $filter
     * @return bool
     * @throws \Exception
     */
    private function _filterFunc($object, $filter) {
        return controllers\filter($object, $filter);
    }

    /**
     * True if this object meets all filter requirements, False otherwise.
     * @param $object: a Model instance
     * @return bool
     */
    private function _checkAgainstFilters($object) {
        $truth = true;
        $filters = (array)$this->_filters;

        if (!count($filters)) {
            // no _filters to check against. equivalent to .all()
            return true;
        }

        foreach ((array)$this->_filters as $idx => $filter) {
            // if any filter is not met, $truth is false
            $truth &= $this->_filterFunc($object, $filter);
            if (!$truth) {
                return false;
            }
        }
        return $truth;
    }

    /**
     * @param {string} $file
     * @return Model: object of Id.Type
     */
    private function _restoreObject($file) {
        // return Pop::obj($this->_moduleName, $file);
        return new ${$this->_moduleName}($file);
    }

    /**
     * Find the directory for this module and return a list of all the
     * _filenames (i.e. object paths).
     *
     * @param $moduleName
     * @return array          absolute paths
     * @throws \Exception
     */
    private static function _findModuleFiles($moduleName) {
        $moduleName = str_replace('\'', DIRECTORY_SEPARATOR, $moduleName);
        $matches = glob(DATA_PATH . $moduleName . DIRECTORY_SEPARATOR . '*',
                        GLOB_MARK|GLOB_ERR);
        $matches2 = [];
        if ($matches === false) {
            throw new \Exception("Failed to glob()!");
        }

        return array_map(function ($path) {
            return basename($path);
        }, $matches);
    }

    /**
     * @return QuerySet  with the same module, filters, and ordering.
     */
    private function _clone() {
        return new self($this->_moduleName,
                        $this->_filters,
                        $this->_orderField);
    }










    /**
     * Return the current element
     * @return mixed Can return any type.
     */
    public function current() {
        return $this->_objects[$this->_position];
    }

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next() {
        ++$this->_position;
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->_position;
    }

    /**
     * Checks if current _position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return isset($this->_objects[$this->_position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->_position = 0;
    }
}
