<?php
    class CSVModel extends Model {

        public function __toString() {
            $bfr = implode(',', $this->properties()) . // header
                   "\n" .
                   implode(',', array_values($this->properties));  // values (not safely escaped)
            return $bfr;
        }
    }