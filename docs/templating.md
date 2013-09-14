# Pop as a templating engine

Pop has a built-in templating engine - a bit strange, considering PHP is
already a templating language. For more information, visit
[Django template tags and filters](https://docs.djangoproject.com/en/dev/ref/templates/builtins/).

Pop supports a core subset of django template tags.

## Supported tags

#### `{{ variable }}`
If `render(array('variable' => 5))`, then it turns into 5.

#### `{% if something %} ... {% endif %}`
If `render(array('something' => true))`, then this block is rendered.
Otherwise, it is not.

#### `{% if something %} ... {% else %} ... {% endif %}`
If `render(array('something' => true))`, then the first block is rendered.
Otherwise, the second block is rendered.

#### `{% if something %} ... {% elseif something_else %} ... {% endif %}`
One of many forms of the `if` tag.

#### `{% if something %} ... {% elseif something_else %} ... {% else %} ... {% endif %}`
One of many forms of the `if` tag.

#### `{% for key, value in an_array %} ... {% endfor %}`
Within the block, `{{ key }}` and `{{ value }}` are available.

**Note**: `key` must be present. `{% for value in an_array %}` is not valid syntax.

#### `{% comprehension_shorthand in an_array %}`
Shorthand for `{% for a, b in an_array %}{{ b }}{% endfor %}`.

#### `{% include "other_template.php" %}`
Reads the file, and replaces this tag with its contents.

#### `{% comment %} contents {% endcomment %}`
Turns contents into nothing.

#### `{% filter func %} contents {% endfilter %}`, where `func` is a php function that accepts `contents`
Turns block contents into `func(contents)`.

There can be multiple `func`s, i.e. `{% filter func1|func2|func3 %}`,
 which renders `func1(func2(func3(contents)))`.