<?php
    define ("DOMAIN", 'http://' . $_SERVER["SERVER_NAME"]);
    define ("PATH", dirname ($_SERVER['SCRIPT_FILENAME']) . '/');
    define ("DATA_PATH", PATH . 'data/');
    define ("MODULE_PATH", PATH . 'modules/');
    define ("LIBRARY_PATH", PATH . 'lib/');
    define ("TEMPLATE_PATH", PATH . 'templates/');
    define ("SITE_TEMPLATE", 'default.php');
    define ("DEFAULT_TEMPLATE", 'default.php');
    // _SERVER["DOCUMENT_ROOT"]	    D:/wamp/www/
    // PATH	                        D:/wamp/www/poop/
    // http://www.ca/kittehs/ => kittehs/    
    
    // SUBDIR: exclude prefix slash, include trailing slash.
    // define ("SUBDIR", substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    define ("SUBDIR", substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    
    // set to false if using POP as persistance library
    define ("USE_POP_REDIRECTION", true);

    define ("MYSQL_USER", '');
    define ("MYSQL_PASSWORD", '');
    define ("MYSQL_HOST", 'localhost');
    define ("MYSQL_DB", 'pop');

    $modules = array (
        'Model',
        'View',
        'Query',
        'Sample',
    );
    
    @chmod (DATA_PATH, 0777);
    if (!is_writable (DATA_PATH)) {
        die ("data path not writable");
    }
?>
