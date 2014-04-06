<?php

namespace Goatherd\WpPlugin\Minify;

use InvalidArgumentException;

/**
 * Derived from Minify_HTML.
 *
 * Copyright (c) Stephen Clay <steve@mrclay.org>
 * Copyright (c) 2014 Maik Penz <maik@phpkuh.de> 
 */
class HtmlMinify implements MinifyInterface
{
    /**
     * @var boolean
     */
    protected $jsCleanComments = true;

    /**@#+
     * internal
     */
    protected $isXhtml;
    protected $replacementHash;
    protected $placeholders = array();
    protected $cssMinifier = 'trim';
    protected $jsMinifier = 'trim';
    /**@#-*/

    /**
     * Create a minifier object
     *
     * @param array  $options
     *
     * 'jsCleanComments' : (optional) whether to remove HTML comments beginning and end of script block
     *
     * 'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
     * unset, minify will sniff for an XHTML doctype.
     */
    public function __construct(array $options = array())
    {
        if (isset($options['xhtml'])) {
            $this->isXhtml = (bool)$options['xhtml'];
        }
        if (isset($options['jsCleanComments'])) {
            $this->jsCleanComments = (bool)$options['jsCleanComments'];
        }
    }

    public function getCssMinifier()
    {
        return $this->cssMinifier;
    }
    
    public function setCssMinifier($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('CssMinifier must be callback.');
        }
        $this->cssMinifier = $callback;
    }
    
    public function getJsMinifier()
    {
        return $this->jsMinifier;
    }
    
    public function setJsMinifier($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('JsMinifier must be callback.');
        }
        $this->jsMinifier = $callback;
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @return string
     */
    public function minify($html)
    {
        if (!isset($this->isXhtml)) {
            $this->isXhtml = (false !== strpos($html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        }

        $this->replacementHash = 'MINIFYHTML_' . md5($_SERVER['REQUEST_TIME']);
        $this->placeholders = array();

        // replace SCRIPTs (and minify) with placeholders
        $html = preg_replace_callback(
            '/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/ui',
            array($this, '_removeScriptCB'),
            $html
        );

        // replace STYLEs (and minify) with placeholders
        $html = preg_replace_callback(
            '/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/ui',
            array($this, '_removeStyleCB'),
            $html
        );

        // remove HTML comments (not containing IE conditional comments).
        $html = preg_replace_callback(
            '/<!--([\\s\\S]*?)-->/',
            array($this, '_commentCB'),
            $html
        );

        // replace PREs with placeholders
        $html = preg_replace_callback(
            '/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/ui',
            array($this, '_removePreCB'),
            $html
        );

        // replace TEXTAREAs with placeholders
        $html = preg_replace_callback(
            '/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/ui',
            array($this, '_removeTextareaCB'),
            $html
        );

        // trim each line.
        $html = preg_replace('/^\\s+|\\s+$/um', '', $html);

        // remove ws around block/undisplayed elements
        $html = preg_replace(
            '/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body'
            . '|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
            . '|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
            . '|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)'
            . '|ul)\\b[^>]*>)/i',
            '$1',
            $html
        );

        // remove ws outside of all elements
        $html = preg_replace(
            '/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</',
            '>$1$2$3<',
            $html
        );

        // use newlines before 1st attribute in open tags (to limit line lengths)
        $html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1\n$2", $html);

        // fill placeholders
        $html = str_replace(
            array_keys($this->placeholders),
            array_values($this->placeholders),
            $html
        );
        // issue 229: multi-pass to catch scripts that didn't get replaced in textareas
        $html = str_replace(
            array_keys($this->placeholders),
            array_values($this->placeholders),
            $html
        );

        return $html;
    }

    protected function commentCB($m)
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<![')) ? $m[0] : '';
    }

    protected function reservePlace($content)
    {
        $placeholder = '%' . $this->replacementHash . count($this->placeholders) . '%';
        $this->placeholders[$placeholder] = $content;

        return $placeholder;
    }

    protected function removePreCB($m)
    {
        return $this->reservePlace("<pre{$m[1]}");
    }

    protected function removeTextareaCB($m)
    {
        return $this->reservePlace("<textarea{$m[1]}");
    }

    protected function removeStyleCB($m)
    {
        $openStyle = "<style{$m[1]}";
        $css = $m[2];
        // remove HTML comments
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);

        // remove CDATA section markers
        $css = $this->removeCdata($css);

        // minify
        $minifier = $this->cssMinifier ? $this->cssMinifier : 'trim';
        $css = call_user_func($minifier, $css);

        return $this->reservePlace(
            $this->needsCdata($css)
            ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
            : "{$openStyle}{$css}</style>"
        );
    }

    protected function removeScriptCB($m)
    {
        $openScript = "<script{$m[2]}";
        $js = $m[3];

        // whitespace surrounding? preserve at least one space
        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[4] === '') ? '' : ' ';

        // remove HTML comments (and ending "//" if present)
        if ($this->jsCleanComments) {
            $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
        }

        // remove CDATA section markers
        $js = $this->removeCdata($js);

        // minify
        $minifier = $this->jsMinifier ? $this->jsMinifier : 'trim';
        $js = call_user_func($minifier, $js);

        return $this->reservePlace(
            $this->needsCdata($js)
            ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
            : "{$ws1}{$openScript}{$js}</script>{$ws2}"
        );
    }

    protected function removeCdata($str)
    {
        return (false !== strpos($str, '<![CDATA[')) ? str_replace(array('<![CDATA[', ']]>'), '', $str) : $str;
    }

    protected function needsCdata($str)
    {
        return ($this->isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}
