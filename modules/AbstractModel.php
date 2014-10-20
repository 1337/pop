<?php

namespace Pop;

abstract class AbstractModel {
    /**
     * @return {Query} for that model class.
     */
    protected function _get_queryset() {
        $class = get_class();
        echo $class;
        return Pop::obj('Query', $class);
    }
}