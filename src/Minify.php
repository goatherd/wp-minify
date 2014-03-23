<?php 

namespace Goatherd\WpPlugin;

/**
 * Request minification.
 *
 * WordPress filters:
 *
 * `gh-minify-veto` disables minify for request if not false
 * `gh-minify-content` can post-process minification
 *
 * Can be used outside wordpress context if autoloaded through composer.
 * For example prepend this code snippet to index.php:
 * 
 * <?php
 * require_once __DIR__ . '/vendor/autoload.php'; // composer
 * Goatherd\WpPlugin\Minify::initPlugin(); // enable minify
 * 
 * That will wrap output and enable minification. Once WordPress is loaded it
 * will enable hooks.
 */
class Minify
{
    // -TODO make option
    protected $doMinify = true;

    // internal
    private static $_instance;

    /**
     *
     * @var boolean
     */
    private static $wordpressEnabled = false;

    /**
     * Singleton init for wordpress.
     *
     * @return self
     */
    public static function initPlugin()
    {
        // run once
        if (!isset(self::$_instance)) {
            self::$_instance = new static();
            self::$_instance->initInstance();
        }

        return self::$_instance;
    }

    /**
     * WordPress integration.
     *
     * @return self
     */
    public static function initWordpress()
    {
        $instance = static::initPlugin();

        if (!static::$wordpressEnabled) {
            static::$wordpressEnabled = true;
        }
    }

    /**
     * 
     */
    public function initInstance()
    {
        ob_start(array($this, 'obCallback'));
    }

    /**
     * Minify cacheable content.
     *
     * Extendable through `gh-minify-content` wp filter.
     * Filter HTML tags to be ignored through `gh-minify-skip-tags`.
     *
     * @param string $content
     *
     * @return string
     */
    public function minifyContent($content)
    {
        // additional check to allow for conditional bypassing later on (especially provide placeholder for backend configuration)
        if ($this->doMinify) {
            /* Ignore this html tags */
            $ignore_tags = array('textarea', 'pre', 'code');
            if (static::$wordpressEnabled) {
                $ignore_tags = (array) apply_filters('gh-minify-skip-tags', $ignore_tags);
            }

            // convert to string
            if ( $ignore_tags ) {
                $ignore_regex = implode('#', $ignore_tags);
                $ignore_regex = preg_quote($ignore_regex, '`');
                $ignore_regex = str_replace('#', '|', $ignore_regex);
            }

            /* Minify */
            $cleaned = preg_replace(
                array(
                    '/<!--[^\[><](.*?)-->/s',
                    '#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' .$ignore_regex. ')\b))*+)(?:<(?>' .$ignore_regex. ')\b|\z))#'
                ),
                array(
                    '',
                    ' '
                ),
                (string) $content
            );

            // only use minified content if not failed
            if ( strlen($cleaned) > 1 ) {
                $content = $cleaned;
                unset($cleaned);
            }
        }

        // allow to futher adjust/ override minification
        if (static::$wordpressEnabled) {
            $cleaned = apply_filters('gh-minify-content', $content);
            if ( strlen($cleaned) > 1 ) {
                $content = $cleaned;
            }
        }

        return $content;
    }

    /**
     * Default veto ruleset applied as first `gh-minify-veto` callback.
     *
     * @return boolean
     */
    protected function vetoMinify()
    {
        // allow to override by user
        if (isset($_GET['x-gh-minify'])) {
            return !$_GET['x-gh-minify'];
        }

        // only minify for index requests
        if (basename($_SERVER['SCRIPT_NAME']) != 'index.php') {
            return true;
        }

        // not when debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }

        return false;
    }

    /**
     * Ob cache callback.
     * 
     * Vetoable by boolean `gh-minify-veto` filter.
     *
     * @param string $content
     *
     * @return string
     */
    public function obCallback($content)
    {
        $veto = $this->vetoMinify();
        if (static::$wordpressEnabled) {
            $veto = apply_filters('gh-minify-veto', $veto);
        }

        if ($veto) {
            return $content;
        }

        return $this->minifyContent($content);
    }
}
