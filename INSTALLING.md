# Installing

### Requirements
* PHP 5.3+
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
