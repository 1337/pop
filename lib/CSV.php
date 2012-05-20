<?php
    class CSV {
        // Comma-separated values
        protected $contents;

        function __construct ($array_of_arrays) {
            $max_cols = 0;
            foreach ($array_of_arrays as $idx => $row) {
                $row_cols = sizeof ($row);
                if ($row_cols > $max_cols) {
                    $max_cols = $row_cols;
                }
            }

            $output = '';
            foreach ($array_of_arrays as &$row) {
                $row = array_pad ($row, $max_cols, '');
                $rtr = str_replace (
                    array ( ',', "\""),
                    array ('\,',  '"'),
                    $row // accepts array $rows
                );
                $output .= '"' . implode ('","', $rtr) . "\"\n";
            }
            $this->contents = $output;
        }

        function __toString() {
            return $this->contents;
        }
    }