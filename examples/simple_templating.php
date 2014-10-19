<?php
    include_once('../pop.php');

    $context = array(
        'title' => 'Hello, World!',
        'names' => array('john', 'jane', 'joe'),
        'print_names' => true
    );
?>

{% if print_names %}
    {% for _, name in names %}
        {% filter ucfirst %}{{ name }}{% endfilter %}
    {% endfor %}
{% else %}
    I am anonymous!
{% endif %}