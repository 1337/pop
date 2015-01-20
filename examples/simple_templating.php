<?php

namespace Example;

error_reporting(E_STRICT|E_ALL|E_DEPRECATED|E_USER_DEPRECATED);

include_once('../pop/load.php');
include_once('../templated/View.php');
include_once('MyModel.php');

$some_model = new MyModel();
$some_model->id = 'baz';
$some_model->foo = 'bar';
$some_model->save();

echo $some_model->foo;

$objects = MyModel::objects()->filter('id__gte', 0)->orderBy('id');
echo $objects;

$objects = MyModel::objects()->orderBy('id');
echo $objects;