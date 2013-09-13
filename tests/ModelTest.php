<?php
    echo getcwd();
    include_once(dirname(dirname(__FILE__)) . '/pop.php');

    class ModelTest extends PHPUnit_Framework_TestCase {

        private $model;

        public function setUp() {
            //
        }

        public function tearDown() {
            $this->model->delete();
        }

        public function testType() {
            $this->model = new Model();
            $this->assertEquals($this->model->type, 'Model');
        }

        public function testAccess() {
            $this->model = new Model(array('a' => 'b'));
            $this->assertEquals($this->model->a, 'b');
        }

        public function testOrOrOr() {
            $this->model = new Model(array('e' => 'c', 'b' => 'd'));
            $this->assertEquals($this->model->a_or_b, 'd');
        }

        public function testArray() {
            $this->model = new Model(array('e' => 'c', 'b' => 'd'));
            is_array($this->model->to_array());
        }

        public function testString() {
            $this->model = new Model(array('e' => 'c', 'b' => 'd'));
            is_string($this->model->to_string());
        }
    }
