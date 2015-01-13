<?php

namespace Example;

include_once('../pop/load.php');
include_once('../templated/View.php');
include_once('MyModel.php');

$some_model = new MyModel();
$some_model->id = 'baz';
$some_model->foo = 'bar';
$some_model->save();

echo $some_model->foo;