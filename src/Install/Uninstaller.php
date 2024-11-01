<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install;

use Tekod\WpLoadGraph\Core\ServiceContainer;
use Tekod\WpLoadGraph\Models\EventStorage;


/**
 * Class Uninstaller.
 */
class Uninstaller
{

    /**
     * Run uninstaller.
     */
    public static function run()
    {
        if (defined('WPLOADGRAPH_PLUGINBASENAME')) {
            return; // probably we have active clone of this plugin, it is better to not touch anything
        }

        // call main "init" to set up constants and execute bootstrap to prepare services
        require __DIR__ . '/../../init.php';

        // late-initialization should not happen, prepare service container manually
        ServiceContainer::getInstance()->init([]);

        // perform uninstallation
        self::uninstallPlugin();
    }


    /**
     * Do uninstallation process.
     * Note that we have initialized services but without check that all requirements are met, use services carefully.
     */
    protected static function uninstallPlugin(): void
    {
        // delete "log" folder
        EventStorage::getInstance()->removeStorageFiles();

        // remove entries from "options" table
        delete_option((new Installer())->getDatabaseVersionKey());

        // clear WP cache
        wp_cache_flush();
    }

}
