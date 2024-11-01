<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install;


/**
 * Class Installer
 */
class Installer
{

    // options key for storing version of database
    protected $dbVersionKey = 'wploadgraph_version';


    /**
     * Install or update database tables if needed.
     */
    public function checkDatabase()
    {
        // load current database version
        $storedDbVersion = get_option($this->dbVersionKey, '0');

        // perform installation tasks if no version stored
        if (!$storedDbVersion) {
            $this->install();
            return;
        }

        // apply updates if stored version is lower
        if (version_compare($storedDbVersion, WPLOADGRAPH_VERSION, '<')) {
            $this->update($storedDbVersion);
        }
    }


    /**
     * Return name of "database version" option.
     *
     * @return string
     */
    public function getDatabaseVersionKey(): string
    {
        return $this->dbVersionKey;
    }


    /**
     * Update structure of the plugin
     *
     * @param string $currentVersion Version from which we're updating
     */
    public function update(string $currentVersion)
    {
        // scan available updates
        $updates = $this->getListOfUpdates();

        // apply each update if
        foreach ($updates as $updateVersion) {
            if (version_compare($currentVersion, $updateVersion, '<')) {
                // run update
                $class = '\Tekod\WpLoadGraph\Install\Updates\Update_' . str_replace('.', '_', $updateVersion);
                $success = (new $class())->update($this);

                // stop loop
                if (!$success) {
                    break;
                }

                // register new version after each successful step
                delete_option($this->dbVersionKey);
                update_option($this->dbVersionKey, $updateVersion, true);
            }
        }

        // send notification
        do_action('wploadgraph_updated');
    }


    /**
     * Search for files "Updates/Update_XYZ.php",
     * extract their XYZ part of name and sort them.
     *
     * @return array
     */
    protected function getListOfUpdates(): array
    {
        $list = [];
        foreach (glob(__DIR__ . '/Updates/*.php') as $file) {
            $parts = explode('/Update_', substr($file, 0, -4));
            if (isset($parts[1]) && is_numeric($parts[1][0])) {
                $version = str_replace('_', '.', $parts[1]);
                $list[] = $version;
            }
        }

        usort($list, 'version_compare');
        return $list;
    }


    /**
     * Perform installation tasks.
     */
    protected function install()
    {
        // .. do tasks (create tables, pages, ...)

        // store actual database version
        update_option($this->dbVersionKey, WPLOADGRAPH_VERSION, true);

        // send notification
        do_action('wploadgraph_installed');
    }

}
