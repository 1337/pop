<?php

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
     * @return {Query} for that class.
     */
    function _get_queryset();
}