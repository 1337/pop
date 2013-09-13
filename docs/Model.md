# Model

The `Model` is a core component of the Pop framework, allowing for object persistence.
It has no default subclasses.

## Properties
Properties are not case-sensitive.
They can be anything [PHP allows](http://www.php.net/manual/en/language.variables.basics.php).

## Public methods

### `Model::get_by_key_name($value)`
Returns the first object in the database whose $property
matches $value.

### `Model::handler()`
Returns the current URL handler, if running in [routing](routing.md) mode.

### `$object = new Model(param=null)`

If no param (null): returns a blank object of type `Model`.

If param is associative array: returns an object with mapping in the array.

If param is not array: returns an object in the database where `id = param`.

### `$object->customFunction(args)`

If this object is registered with extra, object-bound methods,
it will be called like this.

If you want to register methods for all instances of the same
class, then you might want to write a private function.

### `$object->something = "some value"`
Assigns "some value" to something.

If `WRITE_ON_MODIFY` is set to true, your model will be saved immediately.

### `echo $object->a_or_b_or_c`
Prints `$object->a_or_b_or_c`.

If `$object->a_or_b_or_c` doesn't exist, this prints `$object->a`, or, 
if it doesn't exist, `$object->b`.

If `$object->b` doesn't exist either, then `$object->c`.

### `$object->to_string()`
By default, returns a JSON dump of the object.

### `$object->to_array()`
Returns the list of the object's raw properties.

### `$object->properties() {
Returns the list of the object's property names.

### `$object->validate()`
Subclasses can have a `validate` function that throws exceptions.

### `$object->put()`
Saves the object.

### `$object->delete()`
Deletes the object. Pop does not make backups.

### `$object->render($template=null, $more_options=array())`
Uses a View module to show this Model object using a template,
specified or otherwise (using $template).

$template should be a file name, and the file should be present
under VIEWS_PATH.

### `$object->get_db_key()`
Returns something like `db://ModelType/ModelID`.