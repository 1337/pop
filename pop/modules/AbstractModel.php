<?php

namespace Pop;

abstract class AbstractModel {
    protected static $queryset;

    /**
     * @return QuerySet {QuerySet} for that model class.
     * for that model class.
     */
    public static function objects() {
        if (!isset(self::$queryset)) {
            self::$queryset = new QuerySet(get_class());
        }

        return self::$queryset;
    }
}