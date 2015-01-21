# QuerySet

The `QuerySet` is a core component of the Pop framework, allowing for object retrieval.
It has no default subclasses.

## Common usage

### Retrieving the list of `Model` types

    $a = new QuerySet();
    var_dump($a->found);

### Retrieving a list of models of one type

    $a = new QuerySet('Model');
    // or
    $a = Pop::obj('QuerySet', 'ModuleName');
    var_dump($a->get());

### Filtering

    $a = Pop::obj('QuerySet', 'ModuleName');
    // allowed comparisons: >, <, ==, ===, !=, <=, >=, IN, WITHIN, CONTAINS
    $a->filter('id ==', 123);
    var_dump($a->get());

### Chaining

All query methods, with the exception of those that return results, can
be chained if you use PHP 5.3+, i.e. this is possible:

    // get list of 5 women aged between 20 and 40, youngest first.
    $a = Pop::obj('QuerySet', 'People');
    $girls = $a->filter('id !=', null)
               ->filter('sex ===', 'female')
               ->filter('age WITHIN', array(20, 40))
               ->orderBy('age', false)
               ->get(5);


## Public methods

### `$query->get_by_propertyName($value)`

Adds a filter to the query that requires results to have `$model->propertyName == $value`.

### `$query->reject_propertyName($value)`

Adds a filter to the query that requires results *not* to have `$model->propertyName == $value`.

### `(string)$query`

Returns a JSON list of results.

### `$query->toArray()`

Return a list of _objects, which are also lists.

### `$query->filter($filter, $condition)`

Adds a filter to the QuerySet.

$filter = field name followed by an operator, e.g. 'name =='

Comparison operators allowed: >, <, ==, ===, !=, <=, >=, IN, WITHIN, CONTAINS

* `>`: if value is greater than the next parameter.
* `<`: if value is less than the next parameter.
* `==`: if value is equivalent to the next parameter.
* `===`: if value is equal to the next parameter.
* `!=`: if value is not equivalent to the next parameter.
* `<=`: if value is less than or equal to the next parameter.
* `>=`: if value is greater than or equal to the next parameter.
* `IN`: if value, a string, is found as a substring inside the next parameter.
* `WITHIN`: if value, a number, is between the next parameter, `array(min, max)`.
* `CONTAINS`: if value, an array, has a value equal to the next parameter.

### `$query->aggregate_by($key)`

Returns a associative array of _objects, where keys are different values of `Model->$key`.

### `$query->orderBy($key, $asc=true)`

Does exactly what it says it does. Returns the query object.

### `$query->shuffle($strong)`

Shuffles _objects.

If `$strong` is true, then the seeded Fisher-Yates shuffling algorithm will be used.

Returns the query object.

### `$query->fetch($limit=PHP_INT_MAX)`

Finds _objects that meet the query object's filter criteria, if any.

Returns the query object.

### `$query->get($limit=PHP_INT_MAX)`

Returns _objects found by the query, after `fetch` is called.

### `while($object = $query->iterate)`

To speed things up, you don't always need to load all _objects into a giant array.

#### Example

```
$query = Pop::obj('QuerySet', 'People');
while($person = $query->iterate) {
    echo "{$person->name}\n";
}

```

Instead, if _objects are needed for a loop, then this call saves memory.

### `$query->count()`

Returns the number of _objects found by the query.

### `$query->pluck($key)`

Returns an array with only the values of one property from _objects fetched.
