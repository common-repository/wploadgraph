<?php

/**
* Trigger this file on plugin uninstall
*/

declare(strict_types=1);
use Tekod\WpLoadGraph\Install\Uninstaller;


if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// uninstall plugin
require __DIR__ . '/src/Install/Uninstaller.php';
Uninstaller::run();
