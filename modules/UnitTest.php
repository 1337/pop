<?php
    require_once (dirname (__FILE__) . '/Model.php');

    class UnitTest extends Model { 
        /*  How to use:
        
            make a new class e.g. "MyTests"
         
            class MyTests extends UnitTest {
                function test_1 () {
                    $this->assertTrue (true);
                }
            }
         
            ALL functions with "test_" prefix will be run.         
        */
 
        private $error;
        public $resultstack;
 
        function __construct () {
            // get all method names in this class.
            parent::__construct ();

            $this->resultstack = array ();
            $this->setup ();
            error_reporting (E_ALL);
            $methods = get_class_methods (get_class ($this));
            foreach ($methods as $method) {
                if (substr ($method, 0, 5) == "test_") {
                    try {
                        $this->$method ();
                    } catch (Exception $e) {
                        // fail loudly
                    }
                }
            }
            $this->teardown ();
        }
     
        function setup () {
            // extend me to run things before the test.
        }

        function teardown () {
            // extend me to run things this the test.
        }
        
        function cyberpolice () {
            // backtraces. consequences will never be the same
            $trace = array_slice (debug_backtrace (), 3);
            $caller = array_shift ($trace);
            return $caller['function'];
        }
     
        function assertTrue ($x) {         $this->report ($x == true, "was expecting true, got '$x'"); }
        function assertFalse ($x) {        $this->report ($x == false, "was expecting false, got '$x'"); }
        function assertNull ($x) {         $this->report ($x == null, "was expecting null, got '$x'"); }
        function assertNotNull ($x) {      $this->report ($x != null, "was not expecting null, got '$x'"); }
        function assertEqual ($x, $y) {    $this->report ($x == $y, "was expecting '$y', got '$x'"); }
        function assertNotEqual ($x, $y) { $this->report ($x != $y, "was expecting anything but '$x', but got it"); }
        function assertGreater ($x, $y) {  $this->report ($x > $y, "was expecting $x > $y"); }
        function assertLess ($x, $y) {     $this->report ($x < $y, "was expecting $y > $x"); }
     
        function assertIdentical ($x, $y) {
            // Fail if $x === $y is false
            $this->report ($x === $y, "The two objects are not identical");
        }
     
        function assertNotIdentical ($x, $y) {
            $this->report ($x !== $y, "The two objects are identical");
        }
        function assertIsA ($x, $t) {
            // Fail if $x is not the class or type $t
            $this->report (get_class ($x) == $t, "Object is not of type '$t'");
        }
 
        function assertReference ($x, $y) {
            // Fail unless $x and $y are the same variable
            $this->report ($x == $y && $x === $y, "The two variables do not reference the same object in memory");
        }
     
        function assertCopy ($x, $y) {
            // Fail if $x and $y are the same variable
            $this->report ($x == $y && $x !== $y, "The two objects are not copies");
        }
     
        function assertWantedPattern ($p, $x) {
            $this->report (preg_match ($p, $x) == 1, "Wanted pattern was not found in '$x'");
        }
     
        function assertNoUnwantedPattern ($p, $x) {
            $this->report (preg_match ($p, $x) == 0, "Unwanted pattern was found in '$x'");
        }
     
        private function report ($success, $message = '') {
            $methodname = $this->cyberpolice ();
     
            // push results to stack.
            $this->resultstack[] = array (
                'method' => $methodname, 
                'success' => $success, 
                'message' => $success ? '' : $message);
        }
        
        function showResults () {
            $unit_test_class = $this;
            ?>
            <html>
                <head>
                    <style type="text/css">
                        html *, body * {
                            font-family:Verdana, Geneva, sans-serif;
                            font-size: 12px;
                        }
                        #results tr:nth-child(even) {
                            background: #eee;
                        }
                        #results tr:nth-child(odd) {
                            background: #fff;
                        }
                        #results tr td, #results tr th {
                            padding: 5px;
                        }
                        #header {
                            padding: 0 0 30px 0;
                            font-size: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div id="header">
                        <b>UnitTest.php</b>
                        <br />
                        Last updated <?php echo (date ('F j, Y', filemtime (__FILE__))); ?>
                        <br />
                        Tests run <?php echo (date ('H:i:s', time ())); ?>
                    </div>
                    <h2>Class 
                        <?php
                            echo (get_class ($unit_test_class));
                            echo ("<br />");
                            echo ($_SERVER['SCRIPT_FILENAME']);
                        ?>
                    </h2>
                    <table id="results">
                        <tr>
                            <th>#</th>
                            <th>Test</th>
                            <th>Result</th>
                            <th>Expected</th>
                        </tr>
                        <?php
                            $i = 0;
                            foreach ($unit_test_class->resultstack as $result) {
                                $i++;
                                echo ("
                                <tr>
                                    <td>$i</td>
                                    <td>" . $result['method'] . "</td>
                                    <td>" . 
                                        ($result['success'] ? 
                                            "Passed" : 
                                            "<span style='background-color:red;
                                                          color:white;
                                                          padding:4px;
                                                          border-radius:3px;'>
                                                Failed
                                            </span>"
                                        ) . 
                                    "</td>
                                    <td>" . $result['message'] . "</td>
                                </tr>");
                            }
                        ?>
                    </table>
                </body>
            </html>
    <?php
        }
    }
    
?>