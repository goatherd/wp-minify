<?php
/*
Plugin Name: Goatherd Minify
Author: Maik Penz
Plugin URI: https://github.com/goatherd
Description: Plugin HTML minification
Version: 1.0

Requires PHP 5.3 or newer
Requires ob-cache support
*/

// favour autoloading
if (!class_exists('Goatherd\WpPlugin\Minify', true)) {
    require_once __DIR__ . '/src/Minify.php';
}

// let's enable the cache
Goatherd\WpPlugin\Minify::initPlugin();
