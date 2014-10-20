<?php

namespace Pop;

require_once(MODULE_PATH . 'ModelInterface.php');

class CSVModel extends Model implements ModelInterface {

    public function __toString() {
        $bfr = implode(',', $this->properties()) . // header
            "\n" .
            implode(',',
                    array_values($this->properties)); // values (not safely escaped)
        return $bfr;
    }
}