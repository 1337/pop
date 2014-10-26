<?php

namespace Pop;

trait Cacheable {
    // Extended by subclasses.
    // Example $_cache_fields: [guid, id, other_unique_keys]
    protected $_cache_fields = [];

    private function _cache($secondary_keys = true) {
        /*// add to "cache" by indexing this object's _properties.
        // this form of cache is erased after every page load, so it only benefits cases where
        // an object is being read multiple times by different _properties.

        // store by primary key.
        Pop::$models_cache[get_class($this)][$this->_properties['id']] =& $this;

        // store by unique secondary keys.
        if ($secondary_keys) {
            foreach ($this->_cache_fields as $idx => $field) {
                try {
                    // so, key = 'fieldname=value'
                    $key = $field . '=' . (string)$this->__get($field);
                    Pop::$models_cache[get_class($this)][$key] =& $this;
                } catch (\Exception $e) {
                    // cache fail
                }
            }
        }*/
    }
}