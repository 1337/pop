<?php
    /*
        include pop.php if using POP as persistance library:
        <?php
            include_once ('pop/pop.php');

            (the rest of your script)
            $new_pop_model = new Model();

            render();
        ?>

        include index.php (typically not required) if using POP as website manager:
        <?php
            include_once ('pop/index.php');

            (the rest of your script)
            $new_pop_model = new Model();
        ?>
    */

    // @deprecated. Always false.
    const USE_POP_REDIRECTION = false;

    set_time_limit(3); // preferred; prevents DDoS?
    if (isset($_SERVER['SERVER_NAME'])) {
        define(DOMAIN, 'http://' . $_SERVER['SERVER_NAME']);
    } else {
        define(DOMAIN, 'http://localhost'); // phpunit has no clue
    }
    const DATA_PATH = PATH . 'data' . DIRECTORY_SEPARATOR;
    const CACHE_PATH = PATH . 'cache' . DIRECTORY_SEPARATOR;
    const SITE_TEMPLATE = 'default.html';
    const DEFAULT_TEMPLATE = 'default.html';
    const DATA_SUFFIX = '';  // '.json' encouraged
    const FS_FETCH_HARD_LIMIT = PHP_INT_MAX; // when should QuerySet give up?
    const TEMPLATE_COMPRESS = true; // use compressor = more CPU, less bandwidth
    const SITE_SECRET = 'password123'; // for ajax. Change immediately!

    // SUBDIR: exclude prefix slash, include trailing slash.
    // define('SUBDIR', substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    define('SUBDIR', substr(PATH, strlen($_SERVER['DOCUMENT_ROOT'])));

    const WIN = 5;
    $win = WIN;
    const FAIL = 6;
    $fail = FAIL;

    // you CAN store this info elsewhere by including an external file
    // e.g. include ('/var/etc/my_folder/mysql_info.php');
    const MYSQL_USER = '';
    const MYSQL_PASSWORD = '';
    const MYSQL_HOST = 'localhost';
    const MYSQL_DB = 'pop';

    $modules = [
        'Model',
        'View',
        'QuerySet',
    ];