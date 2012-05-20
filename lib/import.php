<?php
    function import() {
        // import files into the global namespace.
        // imoprt('a','b.c','d.e.f') imports a.php, b/c.php, and d/e/f.php
        // from either LIBRARY_PATH, MODULE_PATH, or PATH, in descending
        // order or precedence.

        // of cascading precedence
        $search_roots = array(LIBRARY_PATH, MODULE_PATH, PATH);

        $names = func_get_args();
        foreach ((array) $names as $idx => $include) {
            $path = str_replace ('.', '/', $include) . '.php';
            $imported = false;
            foreach ($search_roots as $idx2 => $search_root) {
                if (file_exists ($search_root . $path)) {
                    $ni = str_replace ('.', '/', $include);
                    include_once ($search_root . $ni . '.php');
                    $imported = true;
                }
            }
            if (!$imported) {
                throw new Exception ('Could not find import: ' . $include);
            }
        }
    }