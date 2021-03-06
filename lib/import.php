<?php
    /**
     * import files into the global namespace.
     * imoprt('a','b.c','d.e.f') imports a.php, b/c.php, and d/e/f.php
     * from either LIBRARY_PATH, MODULE_PATH, or PATH, in descending
     * order or precedence.
     *
     * @param string *args: any number of strings.
     *
     * @throws Exception
     */
    function import() {
        // of cascading precedence
        $search_roots = array(LIBRARY_PATH, MODULE_PATH, PATH);

        $names = func_get_args();
        foreach ((array)$names as $include) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, $include) . '.php';
            $imported = false;
            foreach ($search_roots as $search_root) {
                if (file_exists($search_root . $path)) {
                    $ni = str_replace('.', DIRECTORY_SEPARATOR, $include);
                    include_once($search_root . $ni . '.php');
                    $imported = true;

                    break;
                }
            }
            if ($imported) {
                break;
            }
            // *any* un-importable file will raise an error
            throw new Exception ('Could not find import: ' . $include);
        }
    }