<?php
    require_once(MODULE_PATH . 'ModelInterface.php');

    class MySQLModel extends Model implements ModelInterface {
        /*
            Lets pop use MySQL databases.
            Some results are cached by the underlying Model object.

            define in your config: MYSQL_USER, MYSQL_PASSWORD, MYSQL_HOST, MYSQL_DB
            pop uses only the 'objects' table, with rows:
                <str> 'id' primary
                <str> 'type' unique
                <str> 'props' serialized object
        */
        var $link, $db, $host, $user, $password;

        function __construct($param = null,
            $db = MYSQL_DB,
            $host = MYSQL_HOST,
            $user = MYSQL_USER,
            $password = MYSQL_PASSWORD) {
            parent::__construct($param); // idk...

            $this->_connect($db, $host, $user, $password);
            $this->db = $db;
            $this->host = $host;
            $this->user = $user;
            $this->password = $password;
        }

        public function __get($property) {
            $property = strtolower($property); // case-insensitive
            switch ($property) { // manage special cases
                case 'type':
                    return get_class($this);
                    break;
                default:
                    // first check file system (now used as cache)
                    $cached_val = parent::__get($property);
                    if ($cached_val === null) { // FS returns null if no result.
                        $id = $this->id;
                        // never hurts to call again
                        $this->_connect($this->db, $this->host, $this->user,
                                        $this->password);
                        $sql = "SELECT `properties` FROM `objects` WHERE `id`='$id' LIMIT 1";
                        $ss = mysql_query($sql, $this->link);
                        if ($ss) {
                            if (mysql_num_rows($ss) === 1) { // limit 1, 1 result!
                                $row = mysql_fetch_assoc($ss);
                                // cache result; save to FS cache. line below should call put()
                                $prop_str = $row['properties'];
                                $props = unserialize($prop_str); // unpack

                                // existence of FS object guarantees consistency!
                                // load entire DB object into FS.
                                foreach ((array)$props as $prop_key => $prop_val) {
                                    $this->{$prop_key} = $prop_val;
                                }

                                return $props[$property]; // done caching, return result
                            } else {
                                return null; // no result found in DB either :(
                            }
                        } else {
                            throw new Exception ('Failed to query database');
                        }
                    } else { // result is cached - win!
                        return $cached_val;
                    }
            }
            $this->onRead(); // trigger event
        }

        public function __set($property, $value) {
            // write to DB and reset cache (if you want to keep the cache, feel free to do so)
            parent::__set($property, $value); // access to magic methods

            // never hurts to call again
            $this->_connect($this->db, $this->host, $this->user,
                            $this->password);

            $id = mysql_real_escape_string($this->id, $this->link);
            $type = mysql_real_escape_string($this->type, $this->link);
            $prop_str = mysql_real_escape_string(
                serialize($this->properties), // private variable
                $this->link
            );
            $sql = "INSERT INTO `objects`
                    SET `id` = '$id',
                        `type` = '$type',
                        `properties` = '$prop_str'
                    ON DUPLICATE KEY UPDATE
                        `id` = '$id',
                        `type` = '$type',
                        `properties` = '$prop_str'";
            $ss = mysql_query($sql, $this->link);

            if ($ss) { // if write succeeds, ruin the FS cache.
                @unlink($this->_path());
            } else {
                throw new Exception ('Failed to update database');
            }

            $this->onWrite(); // trigger event
        }

        private function _connect($db, $host, $user, $password) {
            $this->link = mysql_connect($host, $user, $password);
            if (!$this->link) {
                throw new Exception ('Cannot connect to server');
            }
            if (!mysql_select_db($db, $this->link)) {
                throw new Exception ('Cannot use server database');
            }
        }
    }