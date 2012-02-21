<?php
    define ("DOMAIN", 'http://' . $_SERVER["SERVER_NAME"]);
    define ("PATH", dirname ($_SERVER['SCRIPT_FILENAME']) . '/');
    define ("DATA_PATH", PATH . 'data/');
    define ("MODULE_PATH", PATH . 'modules/');
    define ("LIBRARY_PATH", PATH . 'lib/');
    define ("TEMPLATE_PATH", PATH . 'templates/');
    define ("SITE_TEMPLATE", 'index.php');
    define ("DEFAULT_TEMPLATE", 'index.php');
    define ("SUBDIR", substr (PATH, strlen ($_SERVER['DOCUMENT_ROOT'])));
    
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
