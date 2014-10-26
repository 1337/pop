<?php

namespace Pop;


class Model extends AbstractModel implements ModelInterface {
    use Cacheable, Prototypable;
    protected $_properties = []; // associative

    public function __construct($param=null) {
        // if no param (null): create (saved on first __set)
        // if param is associative array: create, with param = default values
        // if param is not array: get as param = id

        if (is_array($param)) {
            // param is default values.
            foreach ($param as $key => $value) {
                $this->__set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Supports the Model::get_by_something syntax.
     * Note: PHP 5.30+ only
     *
     * @param $name
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    /*public static function __callStatic($name, $args) {
        if (substr($name, 0, 7) === 'get_by_') {
            // manage get_by_propname methods with this.
            $prop_name = substr($name, 7); // [get_by_]prop_name
            $query_obj = Pop::obj('QuerySet', get_class());
            return $query_obj->get_by($prop_name, $args[0]);
        } else {
            throw new \Exception('Method ' . $name . ' not registered');
        }
    }*/

    public function __get($property) {
        // Pop uses this method to read all unavailable _properties from the
        // $_properties variable.
        $property = trim(strtolower($property)); // case-insensitive

        // http://php.net/manual/en/language.variables.php
        $var_format = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

        if ($property === 'type') { // manage special cases
            return get_class($this);
        }

        if (isset($this->_properties[$property])) {
            $prop = $this->_properties[$property];

            // checks if the property is a reference to another model.
            // $prop is either a key, which needs taking care of, or not.
            if (self::_is_db_key($prop)) {
                list($class, $id) = self::_info_from_db_key($prop);

                // turn it into an object
                $prop = new ${'Pop\'' . $class}($id);
            }

            return $prop;
        }

        // process variable magic for var_or_var[_or_var]...
        $matches = explode('_or_', $property);
        foreach ($matches as $match) {
            if ($match &&
                preg_match($var_format, $match) &&
                ($this->_properties[$match] !== null)
            ) {
                return $this->_properties[$match];
            }
        }

        return null;
    }

    public function __set($property, $value) {
        $property = trim(strtolower($property)); // case-insensitive

        // save to memory
        $this->_properties[$property] = $value;

        // manage special cases
        if ($property === 'id') {
            // re-initialise
            $this->__construct($value);
        } else if ($property === 'type') {
            // block changes to immutable props
            throw new \Exception('Object type cannot be changed');
        } else if (is_a($value, 'Model')) {
            // replace objects by references, so we can serialize them.
            // this will cause exceptions if the linked object has no key.
            $this->_properties[$property] = $value->get_db_key();
        }

        return $this;
    }

    /**
     * Obtains the model by that id.
     * @param $id
     * @throws \Exception
     */
    public static function get($id) {
        $path = self::_path($id);
        if (!is_file($path)) {
            throw new \Exception('ObjectDoesNotExist');
        }
        try {
            return self::restoreFromFile($path);
        } catch (\Exception $e) {
            throw new \Exception('Read error');
        }
    }

    public function _get_queryset() {

    }

    public function __toString() {
        return json_encode($this->_properties);
    }

    public function toArray() {
        // isn't this what it is?
        return $this->_properties;
    }

    /*public static function get_by($property, $value) {
        // returns the first object in the database whose $property
        // matches $value.
        // e.g. get_by('name', 'bob') => Model(bob)
        $q = Pop::obj('QuerySet', 'Model');
        return $q->filter($property . ' ===', $value)->get();
    }

    public function _properties() { // read-only prop keys
        return array_keys($this->_properties);
    }*/

    public function validate() {
        // @override
        // throw your own exception if anything is wrong.
    }

    /**
     * saves the model to the (filesystem), as a json string.
     * if the WRITE_ON_MODIFY flag is true, you don't need to call this.
     *
     * Pop needs write permission to DATA_PATH and its subdirectories.
     * If it fails to write this model, it raises exceptions.
     *
     * @return int success
     * @throws \Exception
     */
    public function save() {
        // Model checks for its required permission.
        self::_test_writable();

        $this->validate();

        $blob = json_encode($this->_properties);
        $class_dir = dirname($this->_path());
        if (!file_exists($class_dir) && !mkdir($class_dir)) {
            throw new \Exception('Cannot create data directory!');
        }

        return file_put_contents($this->_path(), $blob, LOCK_EX);
    }

    /*public static function handler() {
        // returns the current handler, not the ones
        // for which this module is responsible.
        list($module, $handler) = Pop::url();

        return $handler;
    }*/

    public function get_db_key() {
        // wrapper
        return $this->_key();
    }

    /**
     * @return bool       true if data path is writable.
     * @throws \Exception
     */
    private static function _test_writable() {
        if (!is_writable(DATA_PATH)) {
            throw new \Exception('Path ' . DATA_PATH . ' not writable');
        }
        return true;
    }

    private function _key() {
        if (!isset ($this->_properties['id'])) {
            $this->_properties['id'] = null;
            throw new \Exception('Warning: assigning random ID to unsaved object');
        }
        return $this->_path($this->_properties['id'] || null, true);
    }

    /**
     * The db://ClassName/ID notation means 'this thing is a Model'
     *
     * @param $key
     * @return bool
     */
    private static function _is_db_key($key) {
        return (is_string($key) && substr($key, 0, 5) === 'db://');
    }


    /**
     * returns the filesystem path of an object, created or otherwise,
     * or the database key of an object, created or otherwise.
     *
     * if neither the id is supplied nor the object has an id property,
     * then a unique ID will be used instead.
     *
     * @param string $id
     * @param bool $db_key_format: if true, then db:// replaces data path.
     * @return string
     */
    private function _path($id = null, $db_key_format = false) {
        $root = '';
        $force_save = false;  // turns true if id was automatic

        if ($id === null) {
            if (isset($this->_properties['id'])) {
                // ID is not supplied, but object has it
                $id = $this->_properties['id'];
            } else {
                // ID is neither supplied nor an existing object property
                $id = uniqid('');
                $force_save = true;
            }
        }

        // paths include trailing slash
        if ($db_key_format === true) {
            $root = DATA_PATH;  // data/obj_class/obj_id[.json]
        } else {
            $root = 'db://';  // db://obj_class/obj_id[.json]
        }

        if ($force_save === true) {
            $this->save();
        }

        return $root . get_class($this) . DIRECTORY_SEPARATOR . $id .
               DATA_SUFFIX;
    }

    /**
     * this function does not tolerate failures.
     *
     * @param $db_key: db://class/id
     * @return array ($class_name, $id)
     */
    private static function _info_from_db_key($db_key) {
        $class = substr($db_key, 5, strpos($prop, DIRECTORY_SEPARATOR, 5) - 5); // after 'db://'
        $id = substr($db_key, strpos($prop, DIRECTORY_SEPARATOR, 5) + 1);

        // if the key was appended with DATA_SUFFIX (usually .json),
        // then remove it. it is not part of the id.
        if (substr($id, -strlen(DATA_SUFFIX)) === DATA_SUFFIX) {
            $id = substr($id, 0, -strlen(DATA_SUFFIX));
        }

        return array($class, $id);
    }

    /**
     * Reads a JSON file.
     *
     * @param $path
     * @return array
     */
    private static function _read_from_file($path) {
        $file_contents = file_get_contents($path);
        // json_decode: true = array, false = object
        $props = json_decode($file_contents, true);
        if ($props === null) { // if fails
            throw new \Exception("Could not deserialize $path");
        }

        return $props;
    }
}
