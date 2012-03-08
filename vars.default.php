<?php
    /*
        include pop.php if using POP as persistance library:
        <?php
            include_once ('pop/pop.php');

            (the rest of your script)
            $new_pop_model = new Model ();
        ?>

        include index.php (typically not required) if using POP as website manager:
        <?php
            include_once ('pop/index.php');

            (the rest of your script)
            $new_pop_model = new Model ();
        ?>
    */

    // set to false if using POP as persistance library
    if (!defined ('USE_POP_REDIRECTION')) {
        define ("USE_POP_REDIRECTION", false);
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
