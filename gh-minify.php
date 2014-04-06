<?php
/*
Plugin Name: Goatherd Minify
Author: Maik Penz
Plugin URI: https://github.com/goatherd
Description: Plugin HTML minification
Version: 1.2-alpha

Requires PHP 5.3 or newer
Requires ob-cache support
*/

// favour autoloading
if (!interface_exists('Goatherd\WpPlugin\Minify\MinifyInterface', true)) {
    require_once __DIR__ . '/src/Minify/MinifyInterface.php';
}
if (!class_exists('Goatherd\WpPlugin\Minify\HtmlMinify', true)) {
    require_once __DIR__ . '/src/Minify/HtmlMinify.php';
}
if (!class_exists('Goatherd\WpPlugin\Minify\FastMinify', true)) {
    require_once __DIR__ . '/src/Minify/FastMinify.php';
}
if (!class_exists('Goatherd\WpPlugin\Minify', true)) {
    require_once __DIR__ . '/src/Minify.php';
}

// let's enable the cache
Goatherd\WpPlugin\Minify::initWordpress();
