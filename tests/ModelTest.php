<?php

    include_once('../pop.php');

    class ModelTest extends PHPUnit_Framework_TestCase {

        private $model;

        public function setUp() {
            //
        }

        public function tearDown() {
            $this->model->delete();
        }

        public function testModel1() {
            $this->model = new Model();
            $this->assertEquals($this->model->type, 'Model');
        }

        public function testModel2() {
            $this->model = new Model(array('a' => 'b'));
            $this->assertEquals($this->model->a, 'b');
        }

        public function testModel3() {
            $this->model = new Model(array('e' => 'c', 'b' => 'd'));
            $this->assertEquals($this->model->a_or_b, 'd');
        }
    }
