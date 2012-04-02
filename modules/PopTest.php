<?php 
    require_once (MODULE_PATH . 'UnitTest.php');

    class PopTest extends UnitTest {
        public $thing;
     
        function setup () {
            $this->thing = new Model();
            $this->thing2 = new Model();
        }
        
        // POP TESTS
        function test_model_atomic_prop_rw () {
            $this->thing->derp = 1;
            $this->assertEqual ($this->thing->derp, 1);
        }
        function test_model_poly_prop_rw () {
            $this->thing->derp = array (1,2,3);
            $this->assertEqual ($this->thing->derp, array (1,2,3));
        }
        function test_model_ref_prop_rw () {
            $this->thing->derp = $this;
            $this->assertReference ($this->thing->derp, $this);
        }
        function test_model_ref_assign_rw () {
            $this->thing->derp2 = $this->thing2;
            $this->assertIdentical ($this->thing->derp2, $this->thing2);
        }
        function test_model_ref_persist () {
            // from last test
            $this->assertIdentical ($this->thing->derp2, $this->thing2);
        }
        function test_model_ref_serialize () {
            // from last test
            $this->thing->id = 'test';
            $obj = new Model('test');
            $this->assertEqual ($this->thing, $obj);
        }
        function test_query_count () {
            $this->thing2->id = 'test2';
            $q = new Query ('Model');
            $this->assertEqual ($q->count(), 2);
        }
        function test_query_filter () {
            $this->thing2->abc = 'def';
            $q = new Query ('Model');
            $this->assertEqual (reset ($q->filter('abc ==', 'def')->get()), $this->thing2);
        }
        function test_query_filter_count () {
            $this->thing2->abc = 'def';
            $q = new Query ('Model');
            $this->assertEqual ($q->filter('abc ==', 'def')->count(), 1);
        }
        
        // PHP TESTS
        function test_safe_mode () {
            $this->assertEqual (ini_get ('safe_mode'), 0);
        }
        function test_display_errors () {
            $this->assertEqual (ini_get ('display_errors'), 1);
        }
        function test_allow_url_fopen () {
            $this->assertEqual (ini_get ('allow_url_fopen'), 1);
        }
        function test_detect_unicode () {
            $this->assertEqual (ini_get ('detect_unicode'), 1);
        }
        function test_allow_url_include () {
            $this->assertEqual (ini_get ('allow_url_include'), 1);
        }
        function test_arg_separator_input () {
            $this->assertEqual (ini_get ('arg_separator.input'), '&');
        }
        function test_arg_separator_output () {
            $this->assertEqual (ini_get ('arg_separator.output'), '&');
        }
        function test_disable_functions () {
            $this->assertEqual (ini_get ('disable_functions'), false);
        }
        function test_disable_classes () {
            $this->assertEqual (ini_get ('disable_classes'), false);
        }
        function test_expose_php () {
            $this->assertEqual (ini_get ('expose_php'), 1);
        }
        function test_auto_globals_jit () {
            $this->assertEqual (ini_get ('auto_globals_jit'), 1);
        }
        function test_register_globals () {
            $this->assertEqual (ini_get ('register_globals'), 0);
        }
        function test_gpc_order () {
            $this->assertEqual (ini_get ('gpc_order'), "GPC");
        }
        function test_auto_prepend_file () {
            $this->assertEqual (ini_get ('auto_prepend_file'), null);
        }
        function test_auto_append_file () {
            $this->assertEqual (ini_get ('auto_append_file'), null);
        }
        function test_default_mimetype () {
            $this->assertEqual (ini_get ('default_mimetype'), 'text/html');
        }
        function test_default_charset () {
            $this->assertEqual (ini_get ('default_charset'), '');
        }
        function test_file_uploads () {
            $this->assertEqual (ini_get ('file_uploads'), 1);
        }
        function test_max_file_uploads () {
            $this->assertEqual (ini_get ('max_file_uploads'), 20);
        }
        function test_sql_safe_mode () {
            $this->assertEqual (ini_get ('sql.safe_mode'), 0);
        }
        function test_get_magic_quotes_gpc () {
            $this->assertEqual (get_magic_quotes_gpc (), 0);
        }
        function test_get_magic_quotes_runtime () {
            $this->assertEqual (get_magic_quotes_runtime (), 0);
        }
        
        function run_test () {
            $this->showResults();
        }
    }
?>
