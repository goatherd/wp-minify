<?php

namespace Goatherd\WpPlugin\Minify;

/**
 * Fast, error prone minify.
 *
 * Use this if you have **no** embedded scripts or styles.
 */
class FastMinify implements MinifyInterface
{
    /**
     * (non-PHPdoc)
     * @see \Goatherd\WpPlugin\Minify\MinifyInterface::minify()
     */
    public function minify($html)
    {
        $replace = array(
            '/<!--[^\[><](.*?)-->/s' => '',
            '`(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:textarea|pre|code)\b))*+)(?:<(?>textarea|pre|code)\b|\z))`iu' => ' ',
        );
        $html = preg_replace(array_keys($replace), $replace, $html);

        return $html;
    }
}
