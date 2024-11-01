<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install;


/**
 * Abstract "Update" class.
 */
class AbstractUpdate
{

    /**
     * Updater.
     * Should return true if update is successfully applied.
     *
     * @param Installer $installer
     * @return bool
     */
    public function update(Installer $installer): bool  // phpcs:ignore SlevomatCodingStandard.Classes.MethodSpacing -- comments
    {
        //$this->exampleCreatePages();
        //$this->exampleCreateTables();
        //$this->exampleAlterFields();

        // return success
        return true;
    }

}
