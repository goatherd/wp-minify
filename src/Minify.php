<?php 

namespace Goatherd\WpPlugin;

use Goatherd\WpPlugin\Minify\MinifyInterface;

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
    /**
     * 
     * @var MinifyInterface
     */
    protected $minify;

    // TODO make option
    protected $minifyClass = 'Goatherd\WpPlugin\Minify\HtmlMinify';

    // internal
    private static $_instance;

    /**
     *
     * @var boolean
     */
     static $wordpressEnabled = false;

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

        if (!self::$wordpressEnabled) {
            self::$wordpressEnabled = true;
        }
    }

    /**
     * 
     */
    public function initInstance()
    {
        ob_start(array($this, 'obCallback'));
        $this->setMinify(new $this->minifyClass());
    }

    /**
     * 
     * @param MinifyInterface $minify
     */
    public function setMinify(MinifyInterface $minify)
    {
        $this->minify = $minify;
    }

    /**
     * 
     * @return \Goatherd\WpPlugin\Minify\MinifyInterface
     */
    public function getMinify()
    {
        return $this->minify;
    }

    /**
     *
     * @return boolean
     */
    public function isWordpressEnabled()
    {
        return self::$wordpressEnabled;
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
        if (isset($this->minify)) {
            $content = $this->minify->minify($content);
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

        // plugin inactive or wordpress failed to load
        if (!$this->isWordpressEnabled()) {
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
