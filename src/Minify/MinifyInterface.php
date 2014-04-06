<?php

namespace Goatherd\WpPlugin\Minify;

interface MinifyInterface
{
    /**
     *
     * @param string $html
     *
     * @return string minified html
     */
    public function minify($html);
}