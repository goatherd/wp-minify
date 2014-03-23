gh-minify
=========

WordPress HTML minification plugin.

## Features

Minifies request content for some contents served through WordPress.

* minify HTML
* minify inline Script
* minify inline CSS
* allow to veto through `gh-minify-veto` filter
* allow to post-process minification through `gh-minify-content` filter
* allow to alter tag-ignore list through `gh-minify-skip-tags` filter
* standalone usage

## recommended usage

Prepend this to your WordPress index.php

```php
Goatherd\WpPlugin\Minify::initPlugin(); // enable minify
```

Note that you either need to register composer autoloading
(recommended; for example: `require_once __DIR__ . '/vendor/autoload.php`) or
preload the `Goatherd\WpPlugin\Minify` class file.
 
## technical notes

PSR-4 compliant code approximating [SOLID](http://en.wikipedia.org/wiki/SOLID_%28object-oriented_design%29) priciples.
