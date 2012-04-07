<?php
    class Thing extends Model {
        // Standard adapter for a Things Thing. PHP 5 only.
        public $oid;
        private $cache;

        function __construct ($oid) {
            // if negative, a new object will be created for you automatically, 
            // with the type being the absolute value of $oid (-1 = 1 -> user, -2 = 2 -> group, ...)
            $this->oid = $oid;
            $this->cache = array ();
         
            if ($oid <= 0 && $oid != 0) {
                $oid = $this->Create (); // if object is sure not to exist, create it and update ID
            }
        }

        function Create () {
            $this->put ();
            
            // attempt to delete all old orphans linking to this object because that's impossible.
            // TODO
            
            return $this->id;
        }

        function Type ($type_id = 0) { // gsetter
            if ($type_id === 0) { // no type ID is supplied
                return $this->GetType ();
            } else { // a type ID is supplied
                return $this->SetType ($type_id);
            }
        }

        function GetType () {
            return $this->type;
        }

        function SetType ($type_id) {
            if (ObjectTypeExists ($type_id)) {
                $this->type = $type_id;
            }
            return null;
        }
     
        function GetProps () {
            return $this->properties;
        }
     
        function GetProp ($name) {
            return $this->properties[$name];
        }
     
        function SetProp ($prop, $val) {
            // wrap wrap wrap.
            return $this->SetProps (array ($prop=>$val));
        }
     
        function SetProps ($what) {
            // accepts an array ('name'=>'value','name'=>'value') things and write them.
            if (sizeof ($what) > 0) {
                $this->obj_mtime = time ();
                foreach ($what as $prop => $val) {
                    $this->properties[$prop] = $val;
                }
            }
        }
     
        function DelProps ($what) {
            // accepts an of names, e.g. array ('views','rating', 'status')
            // and deletes all properties with any of those names.
            foreach ($what as $prop) {
                unset ($this->properties[$prop]);
            }
        }

        function DelPropsAll () {
            // removes all properties of this object.
            $this->properties = array ();
        } function DelAllProps () { return $this->DelPropsAll (); }
     
        function GetChildren ($type_id = 0, $order_by = "`child_oid` ASC") {
            // returns all children object IDs associated with this one.
            // if $type_id is supplied, returns only children of that type.
            $oid = $this->oid;
            if ($oid > 0) {
                return $this->children;
            }
            return array (); // everything fails --> return empty array
        }
     
        function SetChildren ($what) {
            // appends a new parent-child relationship into the hierarchy table.
            // accepts array ('child1ID','child2ID',...)
         
            if (!is_array ($what)) {
                $what = array ($what); // a string / int, convert it to string.
            }
         
            if (sizeof ($what) > 0) {
                $oid = $this->oid;
                if ($oid > 0) {
                    foreach ($what as $child) {
                        $child = escape_data ($child);
                        $query = "SELECT `parent_oid` FROM `hierarchy` 
                                    WHERE `child_oid`='$child'
                                      AND `parent_oid`='$oid'";
                        $sql = mysql_query ($query) or die (mysql_error ());
                        if (mysql_num_rows ($sql) == 0) { // no existing key
                            $query = "INSERT INTO `hierarchy` (`parent_oid`,`child_oid`)
                                                         VALUES ('$oid','$child')";
                            $sql = mysql_query ($query) or die ("Error 362: " . mysql_error ());
                        } // else: already there, do nothing
                    }
                    return $sql;
                }
            } else {
                return true; // inserting nothing is a success
            }
        } function SetChild ($what) { return $this->SetChildren (array ($what)); }

        function DelChildren ($child_ids) {
            // removes hierarchical data of some of this object's children.
            // accepts (parent1id, parent2id, ...)
            $oid = $this->oid;
            if ($oid > 0 && sizeof ($child_ids) > 0) {
                $query = "DELETE FROM `hierarchy`
                                 WHERE `parent_oid`='$oid'
                                  AND `child_oid` IN (";
                foreach ($child_ids as $eh) {
                    $eh = escape_data ($eh);
                    $query .= "'$eh',";
                }
                $query = substr ($query, 0, strlen ($query) -1) . ')'; // remove last comma, then add )
                $sql = mysql_query ($query) or die ("Error 385: " . mysql_error () . " | " . $query);
                return $sql;
            }
        }     
     
        function DelChild ($child_id) {
            // might as well
            $this->DelChildren (array ($child_id));
        }
     
        function GetParents ($type_id = 0, $order = "ORDER BY `parent_oid` ASC") {
            // there are no limits to the number of parents.
            // returns all parent objects associated with this one.
            // if $type_id is supplied, returns only parents of that type.
            $oid = $this->oid;
            if (isset ($this->cache['parents']) && sizeof ($this->cache['parents']) > 0) {
                return $this->cache['parents'];
            } else {
                if ($oid > 0) {
                    $children = array ();
                    if ($type_id > 0) {
                        $query = "SELECT ua.`parent_oid`
                                     FROM `hierarchy` as ua, `objects` as ub
                                    WHERE ua.`child_oid` = '$oid'
                                      AND ua.`parent_oid` = ub.`oid`
                                      AND ub.`type` = '$type_id' $order";
                    } else {
                        $query = "SELECT `parent_oid` FROM `hierarchy`
                                    WHERE `child_oid` = '$oid' $order";
                    }
                    $sql = mysql_query ($query) or die (mysql_error ());
                    if ($sql && mysql_num_rows ($sql) > 0) {
                        $roller = array ();
                        while ($tmp = mysql_fetch_assoc ($sql)) {
                            $roller[] = $tmp['parent_oid'];
                        }
                        $this->cache['parents'] = $roller;
                        return $roller;
                    }
                }
                $this->cache['parents'] = array ();
                return array ();
            }
        }
     
        function SetParents ($what) {
            // appends a new parent-child relationship into the hierarchy table.
            // accepts array ('parent1::ID','parent2::ID',...)
            if (sizeof ($what) > 0) {
                $oid = $this->oid;
                $this->cache['parents'] = array (); // flush cache
                foreach ($what as $parent) {
                    $parent = escape_data ($parent);
                    $query = "SELECT `child_oid` FROM `hierarchy` 
                                WHERE `parent_oid`='$parent'
                                  AND `child_oid`='$oid'";
                    $sql = mysql_query ($query) or die (mysql_error ());
                    if (mysql_num_rows ($sql) === 0) { // no existing key
                        $query = "INSERT INTO `hierarchy` (`parent_oid`,`child_oid`)
                                                     VALUES ('$parent','$oid')";
                        $sql = mysql_query ($query) or die (mysql_error ());
                    } // else: already there, do nothing
                }
                return $sql;
            } else {
                return true; // inserting nothing is a success
            }
        }

        function DelParents ($parent_ids) {
            // removes hierarchical data where this object is the parent's child.
            // accepts (parent1id, parent2id, ...)
            $oid = $this->oid;
            if ($oid > 0) {
                $this->cache['parents'] = array (); // flush cache
                $query = "DELETE FROM `hierarchy`
                                 WHERE `child_oid`='$oid'
                                  AND `parent_oid` IN (";
                foreach ($parent_ids as $eh) {
                    $eh = escape_data ($eh);
                    $query .= "'$eh',";
                }
                $query = substr ($query, 0, strlen ($query) -1) . ')';
                $sql = mysql_query ($query) or die (mysql_error ());
                return $sql;
            }
        }
     
        function DelParent ($parent_id) {
            // might as well
            $this->DelParents (array ($parent_id));
        }
     
        function DelParentsAll () {
            // removes hierarchical data where this object is someone's child.
            // effectively removes all of the object's parents (orphanating?).
            $oid = $this->oid;
            if ($oid > 0) {
                $this->cache['parents'] = array (); // flush cache
                $query = "DELETE FROM `hierarchy`
                                 WHERE `child_oid`='$oid'";
                $sql = mysql_query ($query) or die (mysql_error ());
                return $sql;
            }
        } function DelAllParents () { return $this->DelParentsAll (); }
          
        function ChangeID ($nid) {
            // attempt to change the ID of this object to the new ID.
            // attempt to resolve all references to this object.
         
            $query = "SELECT * FROM `objects` WHERE `oid` = '$nid'";
            $sql = mysql_query ($query) or die (mysql_error ());
            if (mysql_num_rows ($sql) == 0) { // target ID does not exist
                $pid = $this->oid;
                $query = "UPDATE `objects` 
                             SET `oid` = '$nid' 
                           WHERE `oid` = '$pid'";
                $sql = mysql_query ($query) or die (mysql_error ());
     
                $query = "UPDATE `hierarchy` 
                             SET `parent_oid` = '$nid' 
                           WHERE `parent_oid` = '$pid'";
                $sql = mysql_query ($query) or die (mysql_error ());
 
                $query = "UPDATE `hierarchy` 
                             SET `child_oid` = '$nid' 
                           WHERE `child_oid` = '$pid'";
                $sql = mysql_query ($query) or die (mysql_error ());
 
                $query = "UPDATE `properties` 
                             SET `oid` = '$nid' 
                           WHERE `oid` = '$pid'";
                $sql = mysql_query ($query) or die (mysql_error ());
                return true;
            } else {
                die ("Failed to reallocate object");
            }
        }
     
        function Duplicate () {
            // creates a data-identical twin of this object.
            // the new twin will have the same parents and have the same children (!!)
            $new_thing = new Thing (0 - $this->GetType ()); // create the object.
         
            $props = $this->GetProps ();
            $new_thing->SetProps ($props); // duplicate properties.
         
            $parents = $this->GetParents ();
            $new_thing->SetParents ($parents); // duplicate upper hierarchy.
         
            $children = $this->Children ();
            $new_thing->SetChildren ($children); // duplicate lower hierarchy.
         
            return $new_thing->oid; // return the object ID. Don't lose it!
        }
     
        function Destroy () {
            // removes an object from the database.
            // deletes the object, the relationship with parents, and their children.
            // children of this object will become orphans.
            global $user;
            if (isset ($user) && get_class ($user) == 'User' && 
                class_exists ('Auth') && function_exists ('CheckAuth')) {
                // stop applies only if the auth library is used
                if (!($user->GetChildren ($this->oid) || 
                    CheckAuth ('administrative privilege', true))) {
                    // CustomException ("You just tried to delete something you do not own.");
                    return false;
                }
            }
            $oid = $this->oid;
            $query = "DELETE FROM `objects`
                             WHERE `oid`='$oid'";
            $sql = mysql_query ($query) or die (mysql_error ()); // delete object first
            $this->DelParentsAll (); // then the properties and stuff (no orphaning on crash)
            $this->DelPropsAll ();
        }
    }
?>
