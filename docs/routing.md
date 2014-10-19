# Running Pop

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

### As a library only
You can also run Pop [as a library](library.md). 
In this mode, Pop will not interfere with your URLs.
[See example](../examples/simple_templating.php)