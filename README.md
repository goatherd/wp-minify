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

## technical notes

PSR-4 compliant code approximating [SOLID](http://en.wikipedia.org/wiki/SOLID_%28object-oriented_design%29) priciples.
