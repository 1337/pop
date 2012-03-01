# Pop

POP is a filesystem-based PHP database, allowing for object persistence without MySQL. 
It is 100% compatible with [backbone.js](http://documentcloud.github.com/backbone/).

## Installing

1. Install your web server
    * In lighttpd, rewrite rules are as follows:  
```
(/etc/lighttpd/lighttpd.conf)

url.rewrite-once = (
"(.*)" => "/pop/index.php"
)
``` 
    * In apache2, your ```.htaccess``` file should have these rules:
```
DirectoryIndex index.php

<IfModule mod_rewrite.c>
  RewriteEngine on

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ index.php [L]

</IfModule>

ErrorDocument 404 /index.php
```

2. Run ```chmod -R 666 (install path)/pop/data``` to allow PHP write access to the data folder.

## Changelog

### 2012-02-14
* Valentine's Day mode added. It is not compatible with MySQLModel.

### 2012-02-12
* Someone should go test the MySQLModel thingy.

### 2012-02-08
* Poop renamed to pop (Pop).
* Pop licensed under GPLv3.
