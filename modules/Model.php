<?php

namespace Pop;

require_once(MODULE_PATH . 'Model/AbstractModel.php');
require_once(MODULE_PATH . 'View.php');
require_once(MODULE_PATH . 'ModelInterface.php');

class Model extends AbstractModel implements ModelInterface {
    protected $properties = array(); // associative
    protected $methods = array();

    // Extended by subclasses.
    // Example $_memcache_fields: [guid, id, other_unique_keys]
    protected $_memcache_fields = array();

    public function __construct($param = null) {
        // if no param (null): create (saved on first __set)
        // if param is associative array: create, with param = default values
        // if param is not array: get as param = id

        if (is_array($param)) {
            // param is default values.
            foreach ($param as $key => $value) {
                $this->__set($key, $value);
            }
        } else if (is_string($param)) { // param is ID.
            $path = $this->_path($param);
            if (is_file($path)) {
                try {
                    $props = self::_read_from_file($path);
                    if ($props) {
                        $this->properties = $props;
                    }
                    $this->properties['id'] = $param; // add ID

                    // cache this object by reference; multi-level key being [{class}][{id}]
                    $this->_memcache();

                } catch (Exception $e) {
                    throw new Exception('Read error');
                }
            } else {
                // not an existing object... create object ONLY IF WE
                // HAVE EXISTING PROPERTIES IN THE BAG
                if (isset($this->properties['id'])) {
                    $this->put();
                }
            }
        }

        return $this;
    }

    /**
     * $methods is an array storing callbacks to functions.
     *
     * if this object is registered with extra, object-bound methods,
     * it will be called like this.
     * if you want to register methods for all instances of the same
     * class, then you might want to write a private function.
     *
     * @param string $name: this function is not actually public.
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $args) {
        if (substr($name, 0, 7) === 'get_by_') {
            // manage get_by_propname methods with this.
            $class_name = get_class();
            $prop_name = substr($name, 7); // [get_by_]prop_name

            return $class_name::get_by($prop_name, $args[0]);

        } else if (isset($this->methods[$name])) {
            return call_user_func_array($this->methods[$name], $args);
        } else {
            throw new Exception('Method ' . $name . ' not registered');
        }
    }

    /**
     * Supports the Model::get_by_something syntax.
     * Note: PHP 5.30+ only
     *
     * @param $name
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($name, $args) {
        if (substr($name, 0, 7) === 'get_by_') {
            // manage get_by_propname methods with this.
            $prop_name = substr($name, 7); // [get_by_]prop_name
            $query_obj = Pop::obj('Query', get_class());
            return $query_obj->get_by($prop_name, $args[0]);
        } else {
            throw new Exception('Method ' . $name . ' not registered');
        }
    }

    public function __get($property) {
        // Pop uses this method to read all unavailable properties from the
        // $properties variable.
        $property = trim(strtolower($property)); // case-insensitive

        // http://php.net/manual/en/language.variables.php
        $var_format = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

        if ($property === 'type') { // manage special cases
            return get_class($this);
        }

        if (isset($this->properties[$property])) {
            $prop = $this->properties[$property];

            // checks if the property is a reference to another model.
            // $prop is either a key, which needs taking care of, or not.
            if (self::_is_db_key($prop)) {
                list($class, $id) = self::_info_from_db_key($prop);

                // turn it into an object
                $prop = Pop::obj($class, $id);
            }

            return $prop;
        }

        // process variable magic for var_or_var[_or_var]...
        $matches = explode('_or_', $property);
        foreach ($matches as $match) {
            if ($match &&
                preg_match($var_format, $match) &&
                ($this->properties[$match] !== null)
            ) {
                return $this->properties[$match];
            }
        }

        return null;
    }

    public function __set($property, $value) {
        $property = trim(strtolower($property)); // case-insensitive

        // save to memory
        $this->properties[$property] = $value;

        // manage special cases
        if ($property === 'id') {
            // re-initialise
            $this->__construct($value);
        } else if ($property === 'type') {
            // block changes to immutable props
            throw new Exception('Object type cannot be changed');
        } else if (is_a($value, 'Model')) {
            // replace objects by references, so we can serialize them.
            // this will cause exceptions if the linked object has no key.
            $this->properties[$property] = $value->get_db_key();
        }

        // check if saving is needed.
        if (WRITE_ON_MODIFY === true && isset($this->properties['id'])) {
            $this->put(); // record it into DB
        }
        return $this;
    }

    public function _get_queryset() {

    }

    public function __toString() {
        return json_encode($this->properties);
    }

    public function to_string() {
        // alias
        return $this->__toString();
    }

    public function to_array() {
        // isn't this what it is?
        return $this->properties;
    }

    private function _memcache($secondary_keys = true) {
        // add to "memcache" by indexing this object's properties.
        // this form of cache is erased after every page load, so it only benefits cases where
        // an object is being read multiple times by different properties.

        // store by primary key.
        Pop::$models_cache[get_class($this)][$this->properties['id']] =& $this;

        // store by unique secondary keys.
        if ($secondary_keys) {
            foreach ($this->_memcache_fields as $idx => $field) {
                try {
                    // so, key = 'fieldname=value'
                    $key = $field . '=' . (string)$this->__get($field);
                    Pop::$models_cache[get_class($this)][$key] =& $this;
                } catch (Exception $e) {
                    // memcache fail
                }
            }
        }
    }

    public static function get_by($property, $value) {
        // returns the first object in the database whose $property
        // matches $value.
        // e.g. get_by('name', 'bob') => Model(bob)
        $q = Pop::obj('Query', 'Model');
        return $q->filter($property . ' ===', $value)->get();
    }

    public function properties() { // read-only prop keys
        return array_keys($this->properties);
    }

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
     * @throws Exception
     */
    public function put() {
        // Model checks for its required permission.
        self::_test_writable() or die();

        $this->validate();

        $blob = json_encode($this->properties);
        $class_dir = dirname($this->_path());
        if (!file_exists($class_dir) && !mkdir($class_dir)) {
            throw new Exception('Cannot create data directory!');
        }

        return file_put_contents($this->_path(), $blob, LOCK_EX);
    }

    public function delete() {
        // objects could never be deleted. whoops.
        try {
            unlink($this->_path());

            return true;
        } catch (Exception $e) {
            Pop::debug($e->getMessage());
        }
    }

    public static function handler() {
        // returns the current handler, not the ones
        // for which this module is responsible.
        list($module, $handler) = Pop::url();

        return $handler;
    }

    /**
     * Router mode
     * uses a View module to show this Model object using a template,
       specified or otherwise (using $template).
     *
     * @param string $template: a file name, present under VIEWS_PATH.
     * @param array $context: template context
     * @return mixed|null
     */
    public function render($template = null, $context = array()) {
        // for historical reasons, $template can be omitted.
        if (is_array($template)) {
            list($template, $context) = array(null, $template);
        }

        if (isset($_json)) {  // global override
            $context['_json'] = $_json;
        }

        if (isset($_cacheable)) {  // global override
            $context['_cacheable'] = $_cacheable;
        }

        if (
            // if a 'json' tag is set to true, the content shall be myself
            (isset($context['_json']) && $context['_json'] === true) ||
            // if template doesn't exist, the content shall be myself too
            (!file_exists(VIEWS_PATH . $template))) {

            echo $this->to_string();
            return;
        }

        $view = new View(
            $template,
            array_merge($this->properties, $context));

        $fc = $view->to_string();

        // cache it if it said it wants to be
        if (isset($context['_cacheable']) && $context['_cacheable'] === true) {
            file_put_contents(
                CACHE_PATH . create_etag($_SERVER['REQUEST_URI']),
                $fc
            );
        }

        echo $fc;  // output it
        return;
    }

    public function get_db_key() {
        // wrapper
        return $this->_key();
    }

    public function get_hash($type = 'read') {
        return md5($this->id . $this->type . $this->field .
                   SITE_SECRET . $type);
    }

    private static function _test_writable() {
        // often used in conjunction with "or die()".
        if (!is_writable(DATA_PATH)) {
            Pop::debug('data path ' . DATA_PATH . ' not writable');
            return false;
        }
        return true;
    }

    private function _key() {
        if (!isset ($this->properties['id'])) {
            $this->properties['id'] = null;
            Pop::debug('Warning: assigning random ID to unsaved object');
        }
        return $this->_path($this->properties['id'] || null, true);
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
            if (isset($this->properties['id'])) {
                // ID is not supplied, but object has it
                $id = $this->properties['id'];
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
            $this->put();
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
     * For compatibility reasons, JSON is the only serialization option,
     * but both JSON and PHP unserialize() are deserialization options.
     *
     * @param $path
     * @return array
     */
    private static function _read_from_file($path) {
        $file_contents = file_get_contents($path);
        // json_decode: true = array, false = object
        $props = json_decode($file_contents, true);
        if ($props === null) { // if fails
            $props = unserialize($file_contents);
        }

        return $props;
    }
}
