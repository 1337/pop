<?php
    require_once (MODULE_PATH . 'View.php');

    class Model {
        protected $properties = array ();
        
        // Extended by subclasses.
        // Example $_memcache_fields: [guid, id, other_unique_keys]
        protected $_memcache_fields = array ();
        
        public function __construct ($param = null) {
            global $_models_cache_;
            // if no param (null): create (saved on first __set)
            // if param is array: create, with param = default values
            // if param is not array: get as param = id
            
            if (is_array ($param)) {
                // param is default values.
                foreach ($param as $key => $value) {
                    $this->__set ($key, $value);
                }
            } else if (is_string ($param)) { // param is ID.
                $path = $this->_path ($param);
                if (is_file ($path)) {
                    try {
                        $file_contents = file_get_contents ($path);
                        // json_decode: true = array, false = object
                        $props = json_decode ($file_contents, true);
                        if ($props === null) { // if fails
                            $props = unserialize ($file_contents);
                        }
                        if ($props) {
                            $this->properties = $props;
                        }
                        $this->properties['id'] = $param; // add ID
                        
                        // cache this object by reference; multi-level key being [{class}][{id}]
                        $this->_memcache ();
                        
                    } catch (Exception $e) {
                        throw new Exception ('Read error');
                    }
                } else {
                    // not an existing object... create object ONLY IF WE
                    // HAVE EXISTING PROPERTIES IN THE BAG
                    if (isset ($this->properties['id']) && sizeof ($this->properties) >= 2) {
                        $this->put ();
                    }
                }
            }
            
            if (!isset ($this->properties['guid'])) {
                // if the object does not have a GUID already, assign one to it.
                $this->__set ('guid', create_guid ());
            }

            $this->onLoad ();
            return $this;
        }
        
        public function __get ($property) {
            $this->onRead (); // trigger event
            $property = strtolower ($property); // case-insensitive
            
            if ($property == 'type') { // manage special cases
                return get_class ($this);
            }
            
            // else
            if (isset ($this->properties[$property])) {
                if (is_string ($this->properties[$property]) && 
                    substr ($this->properties[$property], 0, 5) === 'db://') {
                    // The db://ClassName/ID notation means 'this thing is a Model'
                    $class = substr (
                        $this->properties[$property],
                        5, // after 'db://'
                        strpos ($this->properties[$property], '/', 5) - 5
                    );
                    $id = substr($db, strpos ($db, '/', 5) + 1);
                    return new_object ($id, $class);
                } else {
                    return $this->properties[$property];
                }
            }
            return null;
        }
        
        public function __set ($property, $value) {
            $this->onWrite (); // trigger event

            $property = strtolower ($property); // case-insensitive
            
            if ($value instanceof Model && !is_null ($value->id)) {
                $value = $value->get_db_key (); // replace object by a reference to it, so we can serialize THIS object
            }
            
            $this->properties[$property] = $value;
            
            if ($property === 'id') { // manage special cases
                $this->__construct ($value);
            } else if ($property === 'type') {
                throw new Exception ('Object type cannot be changed');
            } else { // write props into a file if the object has an ID.
                if (isset ($this->properties['id'])) {
                    $this->put (); // record it into DB
                }
            }
        }
        
        public function __toString () {
            return json_encode ($this->properties);
        }
        
        public function to_string () {
            return $this->__toString ();
        }
        
        private function _memcache ($secondary_keys = true) {
            // add to "memcache" by indexing this object's properties.
            // this form of cache is erased after every page load, so it only benefits cases where
            // an object is being read multiple times by different properties.
            
            // store by primary key.
            $_models_cache_[get_class ($this)][$this->properties['id']] =& $this;
            
            // store by unique secondary keys.
            if ($secondary_keys) {
                foreach ($this->_memcache_fields as $idx => $field) {
                    try {
                        $key = $field . '=' . (string) $this->__get($field);
                        $_models_cache_[get_class ($this)][$key] =& $this;
                    } catch (Exception $e) {
                        // memcache fail
                    }
                }
            }
        }
        
        public function ajax_handler () {
            // depending on what the request is like, it will either
            // serve JSON data or modify a Model with the supplied request.
            
            // to request information, send in these fields:
            // - id (object id)
            // - type (object type)
            // - prop (data field)
            // - key (id-type-field-specific read key)
            // returns: (string) val
            
            // to update information, send in these fields, too:
            // - key (id-type-field-specific write key)
            // - val (field value)
            // returns: (string) val

            $id = vars ('id', false);
            $type = vars ('type', false);
            $prop = vars ('prop', false);
            $val = vars ('val', false);
            $key = vars ('key', false);

            try {
                if ($id && $type && $prop) {
                    $obj = new_object ($id, $type);
                    if ($val && $key) { // write
                        $hash = $obj->get_hash ('write');
                        if ($key === $hash) { // key is correct -> update object
                            $obj->$prop = $val;
                        } else { // key is incorrect
                            Header::code(403);
                            die (); // do not serve the json
                        }
                    } else { // read
                        $hash = $obj->get_hash ('read');
                        if ($key !== $hash) { // key is incorrect
                            Header::code(403);
                            die (); // do not serve the json
                        }
                    }
                    // output info
                    $resp = array (
                        'value' => $obj->$prop
                    );
                    echo json_encode ($resp);
                } else { // minimum request params not yet
                    Header::status (400);
                }
            } catch (Exception $e) {
                    Header::status (500);
            }
        }
        
        public static function _get ($id = null, $class_name = null) {
            // allows calls like Model::_get(id)
            if (is_null ($class_name) && function_exists('get_called_class')) {
                $class_name = get_called_class ();
            }
            return new_object ($id, $class_name);
        }
        
        public function properties () { // read-only prop keys
            return array_keys ($this->properties);
        }
        
        public function put () {
            // put is automatically called when a variable is assigned
            // to the object.
            // $blob = serialize ($this->properties);
            
            // Model checks for its required permission.
            if (!is_writable (DATA_PATH)) {
                debug ('data path ' . DATA_PATH . ' not writable');
                die ();
            }
            
            $blob = json_encode ($this->properties);
            if (!mkdir (dirname ($this->_path ()))) {
                throw new Exception ('Cannot create data directory!');
            }
            return file_put_contents ($this->_path (), $blob, LOCK_EX);
        }
        
        public static function handler () {
            // returns the current handler, not the ones 
            // for which this module is responsible.
            list ($module, $handler) = get_handler_by_url ($_SERVER['REQUEST_URI']);
            return $handler;
        }
        
        public function render ($template = null, $more_options = array ()) {
            // shows object structure by default.
            if (is_array ($template)) {
                // swap parameters if template is not given.
                list ($template, $more_options) = array (null, $template);
            }
            
            $this->onBeforeRender (); // trigger event
            
            // open_basedir
            if (file_exists (VIEWS_PATH . $template)) {
                $pj = new View ($template);
                $pj->replace_tags (
                    array_merge ($this->properties, $more_options)
                );
                $fc = $pj->__toString ();
                echo $fc;
                // unset ($pj);
                // cache this thing?
                if (array_key_exists ('_cacheable', $more_options) &&
                    $more_options['_cacheable'] === true) {
                    file_put_contents (
                        CACHE_PATH . create_etag ($_SERVER['REQUEST_URI']), 
                        $fc
                    );
                }
            } else {
                print_r ($this->properties ());
            }
            
            $this->onRender (); // trigger event
        }
        
        function get_db_key () {
            // wrapper
            return $this->_key ();
        }
        
        function get_hash ($type = 'read') {
            return md5 ($this->id . $this->type . $this->field . SITE_SECRET . $type);
        }

        private function _key () {
            if (isset ($this->properties['id'])) {
                return 'db://' . get_class ($this) . '/' . $this->id;
            } else {
                throw new Exception ('Cannot request DB key before ID assignment');
            }
        }
        
        function _path ($id = null) {
            if (!$id) {
                if (isset ($this->properties['id'])) {
                    // ID is not supplied, but object has it
                    $id = $this->properties['id'];
                } else {
                    // ID is neither supplied nor an existing object property
                    $id = uniqid ('');
                }
            }
            return sprintf (
                '%s%s/%s', // data/obj_class/obj_id
                 DATA_PATH, // paths include trailing slash
                 get_class ($this),
                 $id
            );
        }





        // 
        public function onLoad () { }
        public function onBeforeRender () { }
        public function onRender () { }
        public function onRead () { }
        public function onWrite () { }
    }

    // helper
    function new_object ($param = null, $class_name = 'Model') {
        /*  
            Singleton helper
            
            $param can be any of the following:
                - Object ID (string; looks in memcache)
                - 'field=value' (string; looks in memcache)
                - array of properties (creates new object)
        */
        global $_models_cache_;

        try {
            if (!class_exists ($class_name)) {
                // attempt to include the module if it isn't already. scoped include!
                include_once (MODULE_PATH . $class_name . '.php');
            }
            
            if (is_string ($param) && isset ($_models_cache_[$class_name][$param])) {
                return $_models_cache_[$class_name][$param];
            } else {
                // Model::__construct() adds itself to $_models_cache_.
                return new $class_name ($param);
            }
        } catch (Exception $e) {
            debug ('Cannot create object: ' . $e->getMessage ());
        }
    } $_models_cache_ = array (); // cache variable

?>
