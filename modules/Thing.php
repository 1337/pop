<?php

namespace Pop;


class Thing extends Model {
    // Standard adapter for a Things Thing. PHP 5 only.
    public $oid;
    private $cache;

    function __construct($oid) {
        // if negative, a new object will be created for you automatically,
        // with the type being the absolute value of $oid (-1 = 1 -> user, -2 = 2 -> group, ...)
        $this->oid = $oid;
        $this->cache = array();

        if ($oid <= 0 && $oid != 0) {
            $oid = $this->Create(); // if object is sure not to exist, create it and update ID
        }
    }

    /**
     * TODO: attempt to delete all old orphans linking to this object
     * because that's impossible.
     *
     * @return int
     */
    function Create() {
        $this->put();
        return $this->id;
    }

    function Type($type_id = 0) { // gsetter
        if ($type_id === 0) { // no type ID is supplied
            return $this->GetType();
        } else { // a type ID is supplied
            return $this->SetType($type_id);
        }
    }

    /**
     * @return string
     */
    function GetType() {
        return $this->type;
    }

    /**
     * @param string $type_id: the class name of a module.
     * @return null
     */
    function SetType($type_id) {
        $this->type = $type_id;
        return null;
    }

    /**
     * alias for: to_array()
     * @return array
     */
    function GetProps() {
        return $this->properties;
    }

    /**
     * @param string $name
     * @return mixed
     */
    function GetProp($name) {
        return $this->properties[$name];
    }

    /**
     * @param string $prop: key
     * @param mixed $val
     */
    function SetProp($prop, $val) {
        $this->SetProps(array($prop => $val));
    }

    /**
     * @param array $what: array('name'=>'value','name'=>'value')
     */
    function SetProps($what) {
        if (sizeof($what) > 0) {
            $this->obj_mtime = time();
            foreach ($what as $prop => $val) {
                $this->properties[$prop] = $val;
            }
        }
    }

    function DelProps($what) {
        // accepts an of names, e.g. array ('views','rating', 'status')
        // and deletes all properties with any of those names.
        foreach ($what as $prop) {
            unset ($this->properties[$prop]);
        }
    }

    function DelPropsAll() {
        // removes all properties of this object.
        $this->properties = array();
    }

    function DelAllProps() {
        return $this->DelPropsAll();
    }

    /**
     * returns all children object IDs associated with this one.
     * if $type_id is supplied, returns only children of that type.
     *
     * @param int    $type_id: class name of a model subclass.
     * @param string $order_by: this argument has no effect.
     * @return array: results.
     */
    function GetChildren($type_id = 0, $order_by = null) {
        $children = $this->children;
        $buffer = array();
        foreach($children as $child) {
            if ($child->type === $type_id) {
                $buffer[] = $child;
            }
        }
        return $child;
    }

    /**
     * appends a new parent-child relationship into the hierarchy table.
     *
     * @param array $what: ('child1ID','child2ID',...)
     * @return bool
     */
    function SetChildren($what) {
        if (!is_array($what)) {
            $what = array($what); // a string / int, convert it to string.
        }

        foreach((array)$what as $child_id) {
            if (!isset($this->children)) {
                $this->children = array();
            }
            $this->children[] = new Thing($child_id);
        }
        return true; // inserting nothing is a success
    }

    /**
     * Singular of SetChildren.
     *
     * @param string $what: object ID
     * @return bool
     */
    function SetChild($what) {
        return $this->SetChildren(array($what));
    }

    /**
     * removes hierarchical data of some of this object's children.
     *
     * @param array $child_ids: (child_id, child_id, ...)
     * @return bool
     */
    function DelChildren($child_ids) {
        $oid = $this->oid;
        if (sizeof($child_ids) <= 0) {
            return true;  // done
        }
        foreach($child_ids as $child_id) {
            if(($key = array_search($child_id, (array)$this->children)) !== false) {
                unset($this->children[$key]);
            }
        }
    }

    /**
     * Singular of DelChildren.
     *
     * @param string $child_id
     * @return bool
     */
    function DelChild($child_id) {
        $this->DelChildren(array($child_id));
    }

    /**
     * there are no limits to the number of parents.
     * returns all parent objects associated with this one.
     * if $type_id is supplied, returns only parents of that type.
     *
     * @param string $type_id: class name of a model. optional.
     *        TODO: adapter only supports parents of the same type.
     * @param string $order: this parameter has no effect.
     * @return array
     */
    function GetParents($type_id = null, $order = null) {
        $buffer = array();
        $q = new Query($this->type);
        while($obj = $q->iterate()) {
            if ($obj->type === $this->type &&
                in_array($this->id, $obj->children)) {
                $buffer[] = $obj;
            }
        }
        return $buffer;
    }

    /**
     * appends a new parent-child relationship into the hierarchy table.
     *
     * @param array $what: ('parent1::ID','parent2::ID',...)
     * @return bool|resource
     */
    function SetParents($what) {
        /*
        if (sizeof($what) > 0) {
            $oid = $this->oid;
            $this->cache['parents'] = array(); // flush cache
            foreach ($what as $parent) {
                $parent = escape_data($parent);
                $query = "SELECT `child_oid` FROM `hierarchy`
                            WHERE `parent_oid`='$parent'
                              AND `child_oid`='$oid'";
                $sql = mysql_query($query) or die (mysql_error());
                if (mysql_num_rows($sql) === 0) { // no existing key
                    $query = "INSERT INTO `hierarchy` (`parent_oid`,`child_oid`)
                                                 VALUES ('$parent','$oid')";
                    $sql = mysql_query($query) or die (mysql_error());
                } // else: already there, do nothing
            }

            return $sql;
        } else {
            return true; // inserting nothing is a success
        }
        */
    }

    /**
     * removes hierarchical data where this object is the parent's child.
     *
     * @param array $parent_ids: (parent1id, parent2id, ...)
     */
    function DelParents($parent_ids) {
        /*
        $oid = $this->oid;
        if ($oid > 0) {
            $this->cache['parents'] = array(); // flush cache
            $query = "DELETE FROM `hierarchy`
                             WHERE `child_oid`='$oid'
                              AND `parent_oid` IN (";
            foreach ($parent_ids as $eh) {
                $eh = escape_data($eh);
                $query .= "'$eh',";
            }
            $query = substr($query, 0, strlen($query) - 1) . ')';
            $sql = mysql_query($query) or die (mysql_error());

            return $sql;
        }
        */
    }

    function DelParent($parent_id) {
        // might as well
        $this->DelParents(array($parent_id));
    }

    /**
     * removes hierarchical data where this object is someone's child.
     * effectively removes all of the object's parents (orphanating?).
     *
     * @return resource
     */
    function DelParentsAll() {
        /*
        $oid = $this->oid;
        if ($oid > 0) {
            $this->cache['parents'] = array(); // flush cache
            $query = "DELETE FROM `hierarchy`
                             WHERE `child_oid`='$oid'";
            $sql = mysql_query($query) or die (mysql_error());

            return $sql;
        }
        */
    }

    function DelAllParents() {
        return $this->DelParentsAll();
    }

    /**
     * attempt to change the ID of this object to the new ID.
     * attempt to resolve all references to this object.
     *
     * @param int $nid
     * @return bool
     */
    function ChangeID($nid) {
        /*
        $query = "SELECT * FROM `objects` WHERE `oid` = '$nid'";
        $sql = mysql_query($query) or die (mysql_error());
        if (mysql_num_rows($sql) == 0) { // target ID does not exist
            $pid = $this->oid;
            $query = "UPDATE `objects`
                         SET `oid` = '$nid'
                       WHERE `oid` = '$pid'";
            $sql = mysql_query($query) or die (mysql_error());

            $query = "UPDATE `hierarchy`
                         SET `parent_oid` = '$nid'
                       WHERE `parent_oid` = '$pid'";
            $sql = mysql_query($query) or die (mysql_error());

            $query = "UPDATE `hierarchy`
                         SET `child_oid` = '$nid'
                       WHERE `child_oid` = '$pid'";
            $sql = mysql_query($query) or die (mysql_error());

            $query = "UPDATE `properties`
                         SET `oid` = '$nid'
                       WHERE `oid` = '$pid'";
            $sql = mysql_query($query) or die (mysql_error());

            return true;
        } else {
            die ("Failed to reallocate object");
        }
        */
    }

    /**
     * creates a data-identical twin of this object.
     * the new twin will have the same parents and have the same children (!!)
     *
     * @return null
     */
    function Duplicate() {
        $new_thing = new Thing(0 - $this->GetType()); // create the object.

        $props = $this->GetProps();
        $new_thing->SetProps($props); // duplicate properties.

        $parents = $this->GetParents();
        $new_thing->SetParents($parents); // duplicate upper hierarchy.

        $children = $this->GetChildren();
        $new_thing->SetChildren($children); // duplicate lower hierarchy.

        return $new_thing->oid; // return the object ID. Don't lose it!
    }

    /**
     * removes an object from the database.
     * deletes the object, the relationship with parents, and their children.
     * children of this object will become orphans.
     *
     * @return bool: success
     */
    function Destroy() {
        /*
        global $user;
        if (isset ($user) && get_class($user) == 'User' &&
            class_exists('Auth') && function_exists('CheckAuth')
        ) {
            // stop applies only if the auth library is used
            if (!($user->GetChildren($this->oid) ||
                CheckAuth('administrative privilege', true))
            ) {
                // CustomException ("You just tried to delete something you do not own.");
                return false;
            }
        }
        $oid = $this->oid;
        $query = "DELETE FROM `objects`
                         WHERE `oid`='$oid'";
        $sql = mysql_query($query) or die (mysql_error()); // delete object first
        $this->DelParentsAll(); // then the properties and stuff (no orphaning on crash)
        $this->DelPropsAll();
        return true;
        */
    }
}
