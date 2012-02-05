<?php
    class Model {
        private $properties = array ();
        
        public function __construct ($param = null) {
            // if no param (null): create (saved on first __set)
            // if param is array: create, with param = default values
            // if param is not array: get as param = id
            if (isset ($param)) {
                if (is_array ($param)) {
                    // param is default values.
                    foreach ($param as $key => $value) {
                        $this->__set ($key, $value);
                    }
                } else {
                    // param is ID.
                    $path = $this->_path ($param);
                    if (is_file ($path)) {
                        try {
                            $props = unserialize (file_get_contents ($path));
                            if ($props) {
                                $this->properties = $props;
                            }
                            $this->properties['id'] = $param;
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
        
        public function __get($property) {
            $property = strtolower ($property); // case-insensitive
            
            switch ($property) { // manage special cases
                case 'type':
                    return get_class ($this);
                    break;
                default: // write props into a file if the object has an ID.
                    if (array_key_exists ($property, $this->properties)) {
                        return $this->properties[$property];
                        break;
                    } else {
                        // throw new Exception ('accessing invalid property');
                        return null;
                    }
            }
            $this->onRead (); // trigger event
        }
        
        public function __set($property, $value) {
            $property = strtolower ($property); // case-insensitive
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
            return get_handler_by_url ($_SERVER['REQUEST_URI']);
        }
        
        function render ($template = null, $more_options = array ()) {
            // shows object structure by default.
            if (is_array ($template)) {
                // swap parameters if template is not given.
                list ($template, $more_options) = array (null, $template);
            }
            
            $this->onBeforeRender (); // trigger event
            
            if (file_exists (TEMPLATE_PATH . $template)) {
                $pj = new View ($template);
                $props = get_object_vars ($this);
                if (array_key_exists ('properties', $props)) {
                    $pj->ReplaceTags (array_merge ($props['properties'], $more_options));
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
                $q = new Query (get_class ($this));
                return $q->all ();
            } else {
                throw new Exception ('Call all() with an instantiated object, e.g. new Model()->all()');
            }
        }
        
        function filter ($filter, $condition) {
            if (class_exists ('Query') && isset ($this)) {
                $q = new Query (get_class ($this));
                return $q->fetch()->filter ($filter, $condition);
            } else {
                throw new Exception ('Call filter() with an instantiated object');
            }            
        }

        function order ($by, $asc = true) {
            if (class_exists ('Query') && isset ($this)) {
                $q = new Query (get_class ($this));
                return $q->fetch()->order ($by, $asc);
            } else {
                throw new Exception ('Call order() with an instantiated object');
            }            
        }
        
        private function _path ($id = null) {
            if (!$id) {
                if (array_key_exists ('id', $this->properties)) {
                    // ID is not supplied, but object has it
                    $id = $this->properties['id'];
                } else {
                    // ID is neither supplied nor an existing object property
                    $id = uniqid ('', true);
                    // throw new Exception ('Attempting to access object with no ID');
                }
            }
            return sprintf ("%s/%s/%s", // data/obj_class/obj_id
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


    class View {
        //  View handles page templates (Views). put them inside TEMPLATE_PATH.
        var $contents;

        function __construct ($special = '') {
            // if $special (file name) is specified, then that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->ResolveTemplateName ($special); // returns full path
            $this->contents = $this->GetParsed ($template);
        }

        function GetParsed ($file) {
            ob_start();
            include ($file);
            $buffer = ob_get_contents();
            ob_end_clean();
            return $buffer;
        }

        function ResolveTemplateName ($special = '') {
            if (is_file (TEMPLATE_PATH . $special)) {
                return TEMPLATE_PATH . $special;
            } elseif (is_file (TEMPLATE_PATH . SITE_TEMPLATE)) {
                return TEMPLATE_PATH . SITE_TEMPLATE;
            } elseif (is_file (TEMPLATE_PATH . DEFAULT_TEMPLATE)) {
                return TEMPLATE_PATH . DEFAULT_TEMPLATE;
            } else {
                throw new Exception ('nope');
            }
        }

        public function BuildPage () {
            // recursively replace tags that look like
            // <!--inherit file="header_and_footer.php" -->
            // with their actual contents.
            $tag_pattern = '/<!--\s*inherit\s+file\s*=\s*"([^"]+)"\s*-->/';

            $matches = array ();

            // as long as there is still a tag left...
            if (preg_match_all ($tag_pattern, $this->contents, $matches) > 0) {
                if (sizeof ($matches) > 0 && sizeof ($matches[1]) > 0) {
                    foreach ($matches[1] as $filename) { // [1] because [0] is full line
                        if (is_file (TEMPLATE_PATH . $filename)) { // "file exists"
                            $nv = new View ($filename);
                            $nv->BuildPage (); // call buildpage on IT

                            // replace tags in this contents with that contents
                            $this->contents = preg_replace (
                                '/<!--\s*inherit\s+file\s*=\s*"' . addslashes ($filename) . '"\s*-->/', 
                                $nv->contents,
                                $this->contents
                            );
                            unset ($nv);
                        }
                    }
                }
                $matches = array (); // reset matches for next preg_match
            }
        }

        public function ReplaceTags ($tags = array ()) {
            $this->BuildPage (); // recursively include files
            if (sizeof ($tags) > 0) {
                // replace special tags (e.g. tags that must exist)
                $tags = array_merge (array (
                        'title' => '',
                        'content' => ''
                    ), $tags);
                
                // replace tags with object props
                foreach ($tags as $tag => $data) {
                    $data = (string) $data;
                    $data = (file_exists($data))     //decides on
                          ? $this->GetParsed ($data) //file replacement or
                          : $data;                   //string replacement.
                       
                    $this->contents = preg_replace ("/<!-- ?self\." . $tag . " ?-->/i", $data, $this->contents);
                }
                // $this->contents = str_ireplace("<!--root-->", DOMAIN, $this->contents);
                $this->contents = preg_replace ("/<!-- ?root ?-->/i", DOMAIN, $this->contents);
            }
        }

        public function output () {
            echo (html_compress ($this->contents));
        }
    }
?>