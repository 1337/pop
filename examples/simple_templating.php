<?php
    include_once('../pop.php');

    $context = array(
        'title' => 'hello world',
        'names' => array('John', 'Jane', 'Joe'),
        'print_names' => true
    );
?>

{% if print_names %}
    {% for name in names %}
        {% filter ucfirst %}
            {{ name }}
        {% endfilter %}
    {% endfor %}
{% else %}
    I am anonymous!
{% endif %}