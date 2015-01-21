<?php
/**
 * Created by JetBrains PhpStorm.
 * User: brian
 * Date: 11/01/15
 * Time: 5:42 PM
 */

namespace Pop;


interface QuerySetInterface {
    /**
     * @param {string} $filter: field name followed by an operator, e.g. 'name =='
     * @param $condition: one of [<, >, ==, !=, <=, >=, IN]
     * @return $this
     */
    public function filter($filter, $condition);

    /**
     * @param {string} $key: e.g. 'date'
     * @return array
     */
    public function aggregate($key);

    /**
     * Orders all _objects by a key. This is EXTREMELY slow.
     *
     * @param string $by: name of a field
     * @param bool $asc: ascending or descending
     * @return $this
     */
    public function orderBy($by, $asc);
}