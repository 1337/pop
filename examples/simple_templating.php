<?php

namespace Example;

echo "lol";

include_once('../pop/load.php');
include_once('../templated/View.php');
include_once('MyModel.php');

echo "lol";

$some_model = new MyModel();
echo "lol";

$some_model->foo = 'bar';

echo "lol";

$some_model->save();

echo "lol";

echo $some_model->foo;