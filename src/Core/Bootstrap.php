<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Core;

use Tekod\WpLoadGraph\Cron\ShrinkTraceFile;
use Tekod\WpLoadGraph\Dashboard\Dashboard;
use Tekod\WpLoadGraph\Install\Activate;
use Tekod\WpLoadGraph\Install\Deactivate;
use Tekod\WpLoadGraph\Models\Shutdown;


/**
 * Class Bootstrap.
 */
class Bootstrap
{

    // list of unmatched requirements
    protected static $requirements = [];


    /**
     * Start all services.
     */
    public static function init()
    {
        // initialize internal autoloader
        static::initAutoloader();

        // plugin activator / de-activator hooks
        static::initActivationHooks();

        // load service-container function in global namespace
        require __DIR__ . '/functions.php';

        // wait all other plugins to load to continue
        add_action('plugins_loaded', [__CLASS__, 'lateInitialization']);
    }


    /**
     * Deferred tasks for plugin initialization.
     * In this point all other plugins are loaded, so we can check for requirements.
     */
    public static function lateInitialization()
    {
        // we don't want to run high-level functionalities during uninstall process
        if (defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // check are requirements met
        static::requirementsCheck();

        // initialize services
        static::initServices();

        // initialize single-side systems
        if (is_admin()) {
            static::initAdminSystems();
        } else {
            static::initPublicSystems();
        }
    }


    /**
     * Initialize internal autoloader.
     */
    protected static function initAutoloader()
    {
        spl_autoload_register([__CLASS__, 'autoloader'], true, false);
    }


    /**
     * Setup plugin activator / de-activator hooks.
     */
    protected static function initActivationHooks()
    {
        register_activation_hook(WPLOADGRAPH_PLUGINBASENAME, [Activate::class, 'init']);
        register_deactivation_hook(WPLOADGRAPH_PLUGINBASENAME, [Deactivate::class, 'init']);
    }


    /**
     * Initialize features that are needed only on admin side.
     */
    protected static function initAdminSystems()
    {
        // load dashboard pages if not deleted
        if (is_dir(__DIR__ . '/../Dashboard')) {
            Dashboard::init();
        }

        // display notice about unmatched requirements
        if (!empty(static::$requirements)) {
            add_action('admin_notices', static function () {
                $message = 'WpLoadGraph requirements: ' . nl2br(esc_html(implode("\n", static::$requirements)));
                echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
            });
        }

        // register shutdown process for admin pages
        if (empty(static::$requirements)) {
            Shutdown::register();
        }
    }


    /**
     * Initialize features that are needed only on public.
     */
    protected static function initPublicSystems()
    {
        // register shutdown process for public pages
        if (empty(static::$requirements)) {
            Shutdown::register();
        }
    }


    /**
     * Initialize services.
     */
    protected static function initServices()
    {
        // check requirements
        ServiceContainer::getInstance()->init(static::$requirements);

        // register background services if all requirements for this plugin are met
        if (empty(static::$requirements)) {
            ShrinkTraceFile::init();
        }
    }


    /**
     * Check are all plugin requirements are meet.
     * It should populate list of error messages about missing requirements.
     *
     * @return array
     */
    protected static function requirementsCheck(): void
    {
        static::$requirements = [];

        // check plugins
        $plugins = [
            //'WPCF7'   => 'Missing "Contact Form 7" plugin.',
        ];
        foreach ($plugins as $class => $msg) {
            if (!class_exists($class)) {
                static::$requirements[] = $msg;
            }
        }

        // check PHP version
        if (version_compare(PHP_VERSION, '7.4') < 0) {
            static::$requirements['PHP version'] = 'PHP version 7.4 or newer';
        }
    }


    /**
     * Autoloading handler.
     *
     * @param string $class
     * @return null|boolean
     */
    public static function autoloader(string $class): ?bool
    {
        // using "namespace" pattern to locate file
        $parts = array_filter(explode('\\', $class));
        if ($parts[0] !== 'Tekod' || $parts[1] !== 'WpLoadGraph') {
            return null;
        }
        unset($parts[0], $parts[1]);
        $path = __DIR__ . '/../' . implode('/', $parts) . '.php';
        if (!is_file($path)) {
            // not found
            return null;
        }

        // dynamic inclusion
        require $path;

        // success
        return true;
    }

}
