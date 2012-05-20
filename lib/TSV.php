<?php
    class TSV {
        // Tab-separated values
        // new TSV ([[1,2],[3,4],["5\t"]])->__toString() --> 1,2\n3,4
        protected $contents;

        function __construct ($array_of_arrays) {
            $max_cols = 0;
            foreach ($array_of_arrays as $idx => $row) {
                $row_cols = sizeof ($row);
                if ($row_cols > $max_cols) {
                    $max_cols = $row_cols;
                }
            }

            $output = "";
            foreach ($array_of_arrays as &$row) {
                $row = array_pad ($row, $max_cols, "");
                $rtr = str_replace (
                    array ("\t", "\""),
                    array ('\t', '"'),
                    $row // accepts array $rows
                );
                $output .= '"' . implode ("\"\t\"", $rtr) . "\"\n";
            }
            $this->contents = $output;
        }

        function __toString() {
            return $this->contents;
        }
    }