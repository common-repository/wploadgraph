<?php
/**
 * Plugin Name: WpLoadGraph
 * Plugin URI:  https://wordpress.org/plugins/wploadgraph
 * Description: Stress test tool for logging and measuring all requests to your WordPress website and displaying in timeline format.
 * Version:     0.2.3
 * Author:      Miroslav Curcic
 * Author URI:  https://profiles.wordpress.org/tekod
 * Text Domain: wploadgraph
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * License:     GPL v2 or later
 */

declare(strict_types=1);
defined('ABSPATH') || die();

// prevent fatal error if clone of plugin is activated, remember: no function or class declarations in this file
if (defined('WPLOADGRAPH_PLUGINBASENAME')) {
    return;
}


// constants
// phpcs:disable PSR1.Files.SideEffects -- constants
define('WPLOADGRAPH_PLUGINBASENAME', plugin_basename(__FILE__));
define('WPLOADGRAPH_DIR', __DIR__);
define('WPLOADGRAPH_VERSION', '0.2.3'); // plugin version


// load & start plugin
require __DIR__ . '/src/Core/Bootstrap.php';
Tekod\WpLoadGraph\Core\Bootstrap::init();
