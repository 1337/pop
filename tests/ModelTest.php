<?php

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

    public function testOrOr() {
        $this->model = new Model(array('e' => 'c', 'b' => 'd'));
        $this->assertEquals($this->model->a_or_b, 'd');
    }

    public function testOrOrOr() {
        $this->model = new Model(array('e' => 'c', 'b' => 'd', 'g' => 'f'));
        $this->assertEquals($this->model->h_or_m_or_g, 'f');
    }

    public function testArray() {
        $this->model = new Model(array('e' => 'c', 'b' => 'd'));
        $this->assertTrue(is_array($this->model->to_array()));
    }

    public function testString() {
        $this->model = new Model(array('e' => 'c', 'b' => 'd'));
        $this->assertTrue(is_string($this->model->to_string()));
    }

    public function testReferenceSaved() {
        $model1 = new Model();
        $model2 = new Model();

        $model1.put();
        $model2.put();

        $model1->ref = $model2;
        $model1.put();

        $this->assertTrue(is_a($model1->ref, 'Model'));
    }

    public function testReferenceUnsaved() {
        $model1 = new Model();
        $model2 = new Model();

        $model1->ref = $model2;

        $this->assertTrue(is_a($model1->ref, 'Model'));
    }
}
