<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install\Updates;
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

use Tekod\WpLoadGraph\Install\AbstractUpdate;
use Tekod\WpLoadGraph\Install\Installer;


/**
 * Version 0.1.1
 */
class Update_0_1_1 extends AbstractUpdate
{

    /**
     * Updater.
     * Should return true if update is successfully applied.
     *
     * @param Installer $installer
     * @return bool
     */
    public function update(Installer $installer): bool
    {
        // return success
        return true;
    }

}
