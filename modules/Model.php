<?php

    class Model {
        protected $properties = array ();
        
        public function __construct ($param = null) {
            // if no param (null): create (saved on first __set)
            // if param is array: create, with param = default values
            // if param is not array: get as param = id
            if (isset ($param)) {
                // will be overwritten if object loads with existing guid property
                $this->properties['guid'] = create_guid ();

                if (is_array ($param)) {
                    // param is default values.
                    foreach ($param as $key => $value) {
                        $this->__set ($key, $value);
                    }
                } else { // param is ID.
                    $path = $this->_path ($param);
                    if (is_file ($path)) {
                        try {
                            $props = unserialize (file_get_contents ($path));
                            if ($props) {
                                $this->properties = $props;
                            }
                            $this->properties['id'] = $param;
                            
                            // cache this object by reference; key being {class}/{id}
                            // use function $ to get the object back.
                            $_models_cache_[get_class ($this) . '/' . $this->properties['id']] =& $this;
                            
                        } catch (Exception $e) {
                            throw new Exception ('Read error');
                        }
                    } else {
                        // not an existing object... create object ONLY IF WE
                        // HAVE EXISTING PROPERTIES IN THE BAG
                        if (sizeof ($this->properties) >= 2 &&
                            isset ($this->properties['id'])) {
                            $this->put ();
                        }
                    }
                }
            }
            $this->onLoad ();
            return $this;
        }
        
        public function __invoke () {
            // doesn't do anything
        }
        
        public function __get ($property) {
            $property = strtolower ($property); // case-insensitive
            
            switch ($property) { // manage special cases
                case 'type':
                    return get_class ($this);
                    break;
                default: // write props into a file if the object has an ID.
                    if (array_key_exists ($property, $this->properties)) {
                        if (is_string ($this->properties[$property]) && 
                            substr ($this->properties[$property], 0, 5) === "db://") {
                            // notation means "this thing is a Model"
                            // db://ClassName/ID
                            $class = substr ($this->properties[$property],
                                             5,
                                             strpos ($this->properties[$property], '/', 5) - 5);
                            $id = substr($db, strpos ($db, '/', 5) + 1);
                            return new_object ($id, $class);
                        } else {
                            return $this->properties[$property];
                        }
                        break;
                    } else {
                        // throw new Exception ('accessing invalid property');
                        return null;
                    }
            }
            $this->onRead (); // trigger event
        }
        
        public function __set ($property, $value) {
            $property = strtolower ($property); // case-insensitive
            
            if ($value instanceof Model && !is_null ($value->id)) {
                $value = $value->get_db_key (); // replace object by a reference to it, so we can serialize THIS object
            }
            
            $this->properties[$property] = $value;
            
            switch ($property) { // manage special cases
                case 'id': // if id is being set, load other props.
                    $this->__construct ($value);
                    break;
                case 'type': // type is immutable
                    throw new Exception ('Object type cannot be changed');
                    break;
                default: // write props into a file if the object has an ID.
                    if (array_key_exists ('id', $this->properties)) {
                        $this->put (); // record it into DB
                    }
                    break;                
            }
            $this->onWrite (); // trigger event
        }
        
        public function __toString () {
            return json_decode ($this->properties); // if this is useful to you
        }
        
        public function to_string () {
            return $this->__toString ();
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
        
        function put () {
            // put is automatically called when a variable is assigned
            // to the object.
            $blob = serialize ($this->properties);
            @mkdir (dirname ($this->_path ()));
            return file_put_contents ($this->_path (), $blob, LOCK_EX);
        }
        
        function handler () {
            list ($module, $handler) = get_handler_by_url ($_SERVER['REQUEST_URI']);
            return $handler;
        }
        
        function render ($template = null, $more_options = array ()) {
            // shows object structure by default.
            if (is_array ($template)) {
                // swap parameters if template is not given.
                list ($template, $more_options) = array (null, $template);
            }
            
            $this->onBeforeRender (); // trigger event
            
            if (file_exists (TEMPLATE_PATH . $template)) {
                $pj = new_object ($template, 'View');
                $props = get_object_vars ($this);
                if (array_key_exists ('properties', $props)) { // checks if this object is a Model...?
                    $options = array_merge ($props['properties'], $more_options);
                    $pj->expand_page_loops ($options);
                    $pj->replace_tags ($options);
                }
                $pj->output ();
            } else {
                print_r ($this->properties ());
            }
            $this->onRender (); // trigger event
        }
        
        /* Query transduction methods
           example: new Shop()->filter('id ==', 5000)->fetch()->get()
        */
        function all () {
            if (class_exists ('Query') && isset ($this)) {
                $q = new_object (get_class ($this), 'Query');
                return $q->all ();
            } else {
                throw new Exception ('Call all() with an instantiated object, e.g. new Model()->all()');
            }
        }
        
        function filter ($filter, $condition) {
            if (class_exists ('Query') && isset ($this)) {
                $q = new_object (get_class ($this), 'Query');
                return $q->fetch()->filter ($filter, $condition);
            } else {
                throw new Exception ('Call filter() with an instantiated object');
            }            
        }

        function order ($by, $asc = true) {
            if (class_exists ('Query') && isset ($this)) {
                $q = new_object (get_class ($this), 'Query');
                return $q->fetch()->order ($by, $asc);
            } else {
                throw new Exception ('Call order() with an instantiated object');
            }            
        }
        
        function get_db_key () {
            // wrapper
            return $this->_key ();
        }
        
        private function _key () {
            if (array_key_exists ('id', $this->properties)) {
                return 'db://' . get_class ($this) . '/' . $this->id;
            } else {
                throw new Exception ('Cannot request DB key before ID assignment');
            }
        }
        
        function _path ($id = null) {
            if (!$id) {
                if (array_key_exists ('id', $this->properties)) {
                    // ID is not supplied, but object has it
                    $id = $this->properties['id'];
                } else {
                    // ID is neither supplied nor an existing object property
                    $id = uniqid ('');
                    // throw new Exception ('Attempting to access object with no ID');
                }
            }
            return sprintf ("%s%s/%s", // data/obj_class/obj_id
                             DATA_PATH, // paths include trailing slash
                             get_class ($this),
                             $id);
        }





        // 
        public function onLoad () { }
        public function onBeforeRender () { }
        public function onRender () { }
        public function onRead () { }
        public function onWrite () { }
    }

    // helpers
    function new_object ($param = null, $class_name = 'Model') {
        /*  
            retrieve existing object from memory... otherwise, load / make.
            this is something like get_or_create_object_by_name.
            
            $param can be ID or array of properties.
            if properties are supplied, this object is never retrived from mem.
        */
        try {
            if (is_string ($param) && isset ($_models_cache_["$class_name/$param"])) {
                return $_models_cache_["$class_name/$param"];
            } else {
                // Model::__construct() adds itself to $_models_cache_.
                return new $class_name ($param);
            }
        } catch (Exception $e) {
            die ('Cannot create object...');
        }
    } $_models_cache_ = array ();

?>
