# Running Pop

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
To have Pop manage your entire app, consider using its [routing system](routing.md).