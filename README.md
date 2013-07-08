# Pop (GPLv3) ![project logo](http://www.ohai.ca/images/poplogo.jpg)

Pop is a filesystem-based PHP database, allowing for object persistence without MySQL (i.e. NoSQL).

It allows for static object relational mapping.
Pop is 100% compatible with [backbone.js](http://documentcloud.github.com/backbone/).

## What does it do?
### As a database
Pop keeps information across sessions, users, and even servers.
Here is an example of two scripts sharing the same variable:

```
<?php  // script 1
    include('pop.php');

    $var = new Model();
    $var->id = 'secret corporate info';
    $var->contents = 'haha just kidding';

    $var->put();
?>

<?php  // script 2
    include('pop.php');

    $var = new Model('secret corporate info');

    echo $var->contents;  // haha just kidding
?>
```

### As a routing engine
Pop lets you define your own paths in what we call *modules*.
Each module has its own functions that can map to a URL a web browser can visit.

#### Routing example

```
<?php  // modules/HomePage.php
    class HomePage extends Model {
        public function index() {
            $this->render();
        }
    }
?>

# modules/HomePage.yaml
Handlers:
  - /?: index
```

### As a templating engine
Pop has a built-in templating engine - a bit strange, considering PHP is
already a templating language. For more information, visit
[Django template tags and filters](https://docs.djangoproject.com/en/dev/ref/templates/builtins/).

Pop does not support all django template tags.

#### Supported tags
* `{{ variable }}`
* `{% if something %} ... {% endif %}`
* `{% if something %} ... {% else %} ... {% endif %}`
* `{% if something %} ... {% elseif something_else %} ... {% endif %}`
* `{% if something %} ... {% elseif something_else %} ... {% else %} ... {% endif %}`
* `{% for key, value in an array %}`
* `{% comprehension_shorthand in a_list %}`
* `{% include "other_template.php" %}`

## Installing

### Requirements
* PHP 5.3+, or PHP 5.2 + SPL (Standard PHP library; usually built-in)
* JSON module (json_encode, json_decode)
* Apache2, lighttpd, or similar web server with URL rewriting

### Install your web server
If you use lighttpd and want Pop to handle your website, rewrite rules are as follows:

```
(/etc/lighttpd/lighttpd.conf)

url.rewrite-if-not-file = ( "(.*)" => "/pop/index.php" )
```

Then run `/etc/init.d/lighttpd restart`.

If you use apache2 and want Pop to handle your website, your `.htaccess` file should have these rules:

```
DirectoryIndex index.php

<IfModule mod_rewrite.c>
  RewriteEngine on

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ /pop/index.php [L]

</IfModule>

ErrorDocument 404 /index.php
```

Then run `/etc/init.d/apache2 restart`.

2. Run ```chmod -R 666 (install path)/pop/data``` to allow PHP write access to the data folder.

## Changelog

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
