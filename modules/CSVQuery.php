<?php
    class CSVQuery extends Query {

        public function __toString() {
            // CSVQuery requires at least one object present.
            // CSVQuery only works with CSVModels and their subclasses.

            if (!is_array($this->found_objects) ||
                !sizeof($this->found_objects)) {
                throw new Exception("call get() or fetch() first");
            }

            $bfr = '';

            foreach ($this->found_objects as $idx => $obj) {
                if ($idx === 0) {
                    $bfr = (string) $obj;
                } else {
                    $lines = explode("\n", (string) $obj);
                    $bfr .= $lines[1];
                }
                $bfr .= "\n";
            }
            return $bfr;
        }
    }