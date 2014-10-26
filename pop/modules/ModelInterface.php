<?php

namespace Pop;
/**
 * All Models must implement these methods.
 */
interface ModelInterface {
    /**
     * Returns value of that property.
     * @param $property
     * @return mixed
     */
    function __get($property);

    /**
     * Sets that property to that value.
     * @param $property
     * @param $value
     * @return mixed
     */
    function __set($property, $value);

    /**
     * @return {QuerySet} for that class.
     */
    function _get_queryset();

    /**
     * throw your own exception if anything is wrong.
     * @return mixed
     */
    function validate();

    /**
     * Calls validate(), then persists the object.
     * @return mixed
     */
    function save();
}