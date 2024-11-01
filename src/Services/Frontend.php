<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Services;


/**
 * Class Frontend
 */
class Frontend
{

    // list of enqueued JS files that have to be deferred
    protected $deferJavascriptFiles = [];


    /**
     * Singleton getter.
     *
     * @return static
     */
    public static function getInstance(): self
    {
        static $instance;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }


    /**
     * Frontend constructor.
     */
    protected function __construct()
    {
        add_filter('script_loader_tag', [$this, 'onScriptLoaderTag'], 10, 3);
    }


    /**
     * Enqueue asset file.
     *
     * @param string $file
     * @param false  $inFooter
     * @param false  $deferred
     */
    public function enqueueAsset(string $file, bool $inFooter = false, bool $deferred = false)
    {
        $handle = "wploadgraph-$file";
        $ext = strtolower(array_reverse(explode('.', $file))[0]);
        $pluginURL = untrailingslashit(plugin_dir_url(WPLOADGRAPH_DIR . '/.'));

        if ($ext === 'js') {
            wp_enqueue_script($handle, "$pluginURL/assets/$file", [], WPLOADGRAPH_VERSION, $inFooter);
            if ($deferred) {
                $this->deferJavascriptFile($handle);
            }
        } else {
            wp_enqueue_style($handle, "$pluginURL/assets/$file", [], WPLOADGRAPH_VERSION);
        }
    }


    /**
     * Defer some javascript files.
     * This method is listener of "script_loader_tag" filter hook.
     *
     * @param string $tag    The `<script>` tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     * @return string
     */
    public function onScriptLoaderTag(string $tag, string $handle, string $src): string
    {
        return in_array($handle, $this->deferJavascriptFiles, true)
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
            ? '<script src="' . $src . '" id="' . esc_attr($handle) . '" defer="defer"></script>' . "\n"
            : $tag;
    }


    /**
     * Specify enqueued JS file that have to be deferred.
     * Parameter has to be "handle" param used for enqueuing file in wp_enqueue_script().
     * This can be used for files from WP core or another plugins.
     *
     * @param string $handle
     */
    public function deferJavascriptFile(string $handle): void
    {
        $this->deferJavascriptFiles[] = $handle;
    }


    /**
     * Find template to load.
     * It will try to locate "overriding" template in 3 possible folders and fallback to plugin on fail.
     *
     * @param string $path
     * @return string
     */
    public function locateTemplate(string $path): string
    {

        // search for overridden templates
        $located = locate_template(array_filter([

            // search first in "<theme>/woocommerce/." (if we have woocommerce installed)
            function_exists('WC') ? WC()->template_path() . $path : null,

            // search in "<theme>/templates/wploadgraph/."
            'templates/wploadgraph/' . $path,

            // search in "<theme>/templates/."
            'templates/' . $path,
        ]));

        // no problem, get it from plugin
        $pluginPath = WPLOADGRAPH_DIR . '/templates/' . $path;
        if (!$located && file_exists($pluginPath)) {
            return $pluginPath;
        }

        // ret
        return $located;
    }


    /**
     * Load template nad return its content.
     *
     * @param string $path
     * @param array  $vars
     */
    public function renderTemplate(string $path, array $vars = []): void
    {
        // get template location
        $path = $this->locateTemplate($path);
        $path = apply_filters("wploadgraph_template_$path", $path, $vars);
        if (!$path) {
            return;
        }

        // load
        include $path;
    }

}
