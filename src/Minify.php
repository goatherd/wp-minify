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
 */
class Minify
{
    // -TODO make option
    protected $doMinify = true;

    // internal
    private static $_instance;
    
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
            $ignore_tags = (array) apply_filters('gh-minify-skip-tags', $ignore_tags);
    
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
        $cleaned = apply_filters('gh-minify-content', $content);
        if ( strlen($cleaned) > 1 ) {
            $content = $cleaned;
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
        // only minify for index requests
        if (basename($_SERVER['SCRIPT_NAME']) != 'index.php') {
            return true;
        }

        // -TODO add default conditions where minify should be disabled

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
        if (apply_filters('gh-minify-veto', $this->vetoMinify())) {
            return $content;
        }

        return $this->minifyContent($content);
    }
}
