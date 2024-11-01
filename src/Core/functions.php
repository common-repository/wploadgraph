<?php
/**
 * Global functions declared by this plugin.
 */

declare(strict_types=1);
// phpcs:disable NeutronStandard.Globals.DisallowGlobalFunctions

/**
 * Register function that expose plugin interface.
 */
function wploadgraph(): Tekod\WpLoadGraph\Core\ServiceContainer
{
    return Tekod\WpLoadGraph\Core\ServiceContainer::getInstance();
}
