# Pop as a templating engine
Pop has a built-in templating engine - a bit strange, considering PHP is
already a templating language. For more information, visit
[Django template tags and filters](https://docs.djangoproject.com/en/dev/ref/templates/builtins/).

Pop supports a core subset of django template tags.

#### Supported tags
* `{{ variable }}`
* `{% if something %} ... {% endif %}`
* `{% if something %} ... {% else %} ... {% endif %}`
* `{% if something %} ... {% elseif something_else %} ... {% endif %}`
* `{% if something %} ... {% elseif something_else %} ... {% else %} ... {% endif %}`
* `{% for key, value in an array %}`
* `{% comprehension_shorthand in a_list %}`
* `{% include "other_template.php" %}`