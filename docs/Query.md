# Query

The `Query` is a core component of the Pop framework, allowing for object retrieval.
It has no default subclasses.

## Common usage

### Retrieving the list of `Model` types

    $a = new Query();
    var_dump($a->found);

### Retrieving a list of models of one type

    $a = new Query('Model');
    // or
    $a = Pop::obj('Query', 'ModuleName');
    var_dump($a->get());

### Filtering

    $a = Pop::obj('Query', 'ModuleName');
    $a->filter('id ==', 123);  // allowed comparisons: >, <, ==, !=, <=, >=, IN
    var_dump($a->get());


## Public methods

### `$query->get_by_propertyName($value)`

Adds a filter to the query that requires results to have `$model->propertyName == $value`.

### `$query->to_string()`

Returns a JSON list of results.

### `$query->to_array()`

Return a list of objects, which are also lists.

### `$query->filter($filter, $condition)`

Adds a filter to the Query.

$filter = field name followed by an operator, e.g. 'name =='

Comparison operators allowed: <, >, ==, !=, <=, >=, IN

### `$query->aggregate_by($key)`

Returns a associative array of objects, where keys are different values of `Model->$key`.

### `$query->order($by, $asc=true)`

Does exactly what it says it does. Returns the query object.

### `$query->shuffle($strong)`

Shuffles objects.

If `$strong` is true, then the seeded Fisher-Yates shuffling algorithm will be used.

Returns the query object.

### `$query->fetch($limit=PHP_INT_MAX)`

Finds objects that meet the query object's filter criteria, if any.

Returns the query object.

### `$query->get($limit=PHP_INT_MAX)`

Returns objects found by the query, after `fetch` is called.

### `while($object = $query->iterate)`

To speed things up, you don't always need to load all objects into a giant array.

Instead, if objects are needed for a loop, then this call saves memory.

### `$query->count()`

Returns the number of objects found by the query.

### `$query->pluck($key)`

Returns an array with only the values of one property from objects fetched.

### `$query->min($key)`

Returns the object by which its $key was the smallest.

### `$query->max($key)`

Returns the object by which its $key was the largest.