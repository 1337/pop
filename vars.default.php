<?php
    /*
        set USE_POP_REDIRECTION to false if using POP as persistance library:
        <?php
            define ("USE_POP_REDIRECTION", false);
            include_once ('pop/index.php');
            
            $new_pop_model = new Model ();
            
            (the rest of your script)
        ?>
    */

    // set to false if using POP as persistance library
    if (!defined ('USE_POP_REDIRECTION')) {
        define ("USE_POP_REDIRECTION", true);
    }

    set_time_limit (3); // preferred; prevents DDoS?
    define ("DOMAIN", 'http://' . $_SERVER["SERVER_NAME"]);
    define ("PATH", dirname ($_SERVER['SCRIPT_FILENAME']) . '/');
    define ("DATA_PATH", PATH . 'data/');
    define ("MODULE_PATH", PATH . 'modules/');
    define ("LIBRARY_PATH", PATH . 'lib/');
    define ("VIEWS_PATH", PATH . 'views/');
    define ("TEMPLATE_PATH", VIEWS_PATH . 'templates/');
    define ("STATIC_PATH", 'static/'); // cannot be changed
    define ("SITE_TEMPLATE", 'default.php');
    define ("DEFAULT_TEMPLATE", 'default.php');
    define ("EXTRA_TEMPLATE_TAG_FORMATS", false); // allow {{ tags }} ?
    
    // SUBDIR: exclude prefix slash, include trailing slash.
    // define ("SUBDIR", substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    define ("SUBDIR", substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));

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
?>
