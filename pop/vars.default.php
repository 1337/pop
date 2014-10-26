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
    define('USE_POP_REDIRECTION', false);

    set_time_limit(3); // preferred; prevents DDoS?
    if (isset($_SERVER['SERVER_NAME'])) {
        define('DOMAIN', 'http://' . $_SERVER['SERVER_NAME']);
    } else {
        define('DOMAIN', 'http://localhost'); // phpunit has no clue
    }
    define('DATA_PATH', PATH . 'data' . DIRECTORY_SEPARATOR);
    define('CACHE_PATH', PATH . 'cache' . DIRECTORY_SEPARATOR);
    define('SITE_TEMPLATE', 'default.html');
    define('DEFAULT_TEMPLATE', 'default.html');
    define('DATA_SUFFIX', '');  // '.json' encouraged
    define('FS_FETCH_HARD_LIMIT', PHP_INT_MAX); // when should QuerySet give up?
    define('TEMPLATE_COMPRESS', true); // use compressor = more CPU, less bandwidth
    define('SITE_SECRET', 'password123'); // for ajax. Change immediately!

    // SUBDIR: exclude prefix slash, include trailing slash.
    // define('SUBDIR', substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    define('SUBDIR', substr(PATH, strlen($_SERVER['DOCUMENT_ROOT'])));

    define('WIN', 5);
    $win = WIN;
    define('FAIL', 6);
    $fail = FAIL;

    // you CAN store this info elsewhere by including an external file
    // e.g. include ('/var/etc/my_folder/mysql_info.php');
    define('MYSQL_USER', '');
    define('MYSQL_PASSWORD', '');
    define('MYSQL_HOST', 'localhost');
    define('MYSQL_DB', 'pop');

    $modules = array(
        'Model',
        'View',
        'QuerySet',
    );