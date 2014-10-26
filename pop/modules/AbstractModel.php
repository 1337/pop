<?php

namespace Pop;

abstract class AbstractModel {
    /**
     * @return {QuerySet} for that model class.
     */
    protected function _get_queryset() {
        $class = get_class();
        echo $class;
        // return Pop::obj('QuerySet', $class);
        return new QuerySet($class);
    }
}