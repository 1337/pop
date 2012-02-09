<?php
    class MySQLModel extends Model {
        /* 
            Lets pop use MySQL databases.
            Some results are cached by the underlying Model object.
            
            define in your config: MYSQL_USER, MYSQL_PASSWORD, MYSQL_HOST, MYSQL_DB
            pop uses only the 'objects' table, with rows:
                <str> 'id' primary
                <str> 'type' unique
                <str> 'props' serialized object
        */
        var $link;
        
        function __construct ($param = null,
                              $db = MYSQL_DB, 
                              $host = MYSQL_HOST, 
                              $user = MYSQL_USER, 
                              $password = MYSQL_PASSWORD) {
            parent::__construct ($param); // idk...
            $this->_connect ($db, $host, $user, $password);
        }
        
        public function __get ($property) {
            $property = strtolower ($property); // case-insensitive
            
            switch ($property) { // manage special cases
                case 'type':
                    return get_class ($this);
                    break;
                default: // write props into a file if the object has an ID.
                    // first check file system.
                    $cached_val = parent::__get ($property);
                    if (is_null ($cached_val)) { // FS returns null if no result.
                        $id = $this->id;
                        $sql = "SELECT `$property` FROM `objects` WHERE `id`='$id' LIMIT 1";
                        $ss = mysql_query ($sql, $this->link);
                        if ($ss) {
                            $row = mysql_fetch_assoc ($ss);
                            $this->{$property} = $row[$property]; // cache result
                            return $row[$property];
                        } else {
                            throw new Exception ('failed to query database');
                        }
                    } else { // result is cached - win!
                        return $cached_val;
                    }
            }
            $this->onRead (); // trigger event
        }
        
        public function __set ($property, $value) {
            //
        }
        
        private function _connect ($db, $host, $user, $password) {
            $this->link = mysql_connect ($host, $user, $password);
            if (!$this->link) {
                throw new Exception ('Cannot connect to server');
            }
            if (!mysql_select_db ($db, $this->link)) {
                throw new Exception ('Cannot use server database');
            }
        }
    }
?>