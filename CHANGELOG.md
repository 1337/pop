# Changelog

### 2013-09-14
* Added tests.
* Added `filter` and `comment` template tags.
* Added piped syntax to `filter` tag.
* Templating engine now enforces a maximum iteration count of 1000. Typically, 1 ~ 5 iterations complete templating.
* Fixed HTTP status code library (it didn't work half of the time)
* Removed the `field` tag. It is not fundamental enough to be in the core.

### 2013-09-13
* Added `to_array` to Models and Query objects.
* Added `min`, `max`, `pluck` and `shuffle`.
* Added `Thing` adapter.
* Added `StaticServer` component.

### 2013-09-11
* Added `CSVModel` and `CSVQuery`.
* Added `__toString` magics for `Query`, `CSVQuery`, and `CSVModel`.
* Added optional math library.

### 2013-09-10
* Added `Query->aggregate_by($key) => (array of arrays)`

### 2013-07-08
* Added the `Query->get_by_some_field($value)` syntax

### 2013-03-16
* Fixed problem where templates with no branching logic fails to render (really this time)

### 2013-01-03
* Added option to have settings files above the library directory
* Fixed problem where templates with no branching logic fails to render

### 2012-12-20
* Fixed dependency of View on AjaxField, which was not available on github.

### 2012-06-20
* Added flag to skip object persistence until $model.put() is called. It is a huge performance booster if used correctly.

### 2012-05-20
* Calling `render()` is no longer required in the case that you use Pop
  purely as a rendering engine.
* Fixed bug involving static file URLs (when they go missing).
* Fixed bug with list comprehension template replacement (`{{ x in all_things }}`)
* Removed HTML comment mode for templating (as it processes html comments as well)

### 2012-04-10
* Added object instance prototyping: you can now add functions to individual objects:

```
$a = new Model();
$a->methods['foo'] = function (arg) {
    echo 'bar';
};
```

* Changed core to use the Standard PHP Library (SPL), because it seems to be available everywhere.
* Moved core to become a module; closed many Pop-specific functions into it.
* Fixed bug associated with `strcasecmp`.
* Misc improvements.

### 2012-03-17
* New `AjaxField` class renders a HTML input/textarea tag corresponding to a given object's field:

```
{% field (object id) (object class) (object property) %}
```

### 2012-03-09
* Query can now be called iteratively. With this pattern, the Query class can fetch thousands of objects without memory shortage.

```
while ($object = $query->iterate ()) {
    do_something_with ($object);
    unset ($object);
}
```

* Several template shorthands have been added. For example, `{% tag in tags %}` is equivalent to `{% for i,tag in tags %}{{ tag }}{% endfor %}`.
* Added absolute file system fetch limit. If a given Model type has thousands of instances and you want just the top 5, this might be a good idea.

### 2012-03-07
* Modules now manage their own dependencies. Use `require_once (dirname (__FILE__) . '/Model.php');` to include the Model class, for example.
* URLs are now managed by YAML, using [Spyc](http://code.google.com/p/spyc/). Here is an example (School.yaml) of how it works.

```
Handlers:
  - /index.(htm|html|php): show_homepage
  - /schools.(htm|html|php): show_schools
```


### 2012-03-04
* Added support for template conditional tags:

```
    {% if something %}
        something
    {% elseif something_else %}
        something_else?
    {% else %}
        nothing else available!
    {% endif %}
```

### 2012-03-04
* Added Thing adaptor for [Things](http://github.com/1337/things). You can use `new Thing ($oid)` exactly the same way.
* Improved UnitTest UI.
* Improved Query class performance: filters are now only processed on-demand.
* Simplified Query syntax: `$query_class->all()->filter('property IN', $values)->fetch()->get()` can be done with `$query_class->filter('property IN', $values)->get()`.
* Removed `Subclass.query_functions()` because it is unorthodox.

### 2012-03-03
* You can now use associative arrays:

```
<ul>
    {% for key,val in store %}
    <li>{{ key }}, {{ val }}</li>
    {% endfor %}
</ul>
```

### 2012-03-02
* Added template loops: if template is rendered with option `'array' => array(1,2,3,4)`, then `{% for x in array %}{{ x }}{% endfor %}` would print '1234'.
* Added recursive template inclusion. Also, you can now add template inclusion tags as part of an object.
* Added support for template snippets in subfolders.
* Simplified template tags: you can now use `{{ property }}` in place of `{{ self.property }}`.
* Simplified template inclusion tag: you can now use `{% include "file.html" %}` in place of `{% inherit file="file.html" %}`.
* Improved speed of template rendering, with ~10% memory savings.
* Added tag properties: memory usage (`{{ memory_usage }}`), subdirectories (`{{ subdir }}`), and current handler (`{{ handler }}`).

### 2012-03-01
* Changed storage format from serialized PHP to JSON. It is still possible to read serialized PHP objects, but they will be re-saved as JSON.
* CSS/JS compressors now accept multiple filenames, and concatenates them when serving.
* Misc cross-machine bug fixes.
