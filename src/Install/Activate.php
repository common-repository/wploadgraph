<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install;


use Tekod\WpLoadGraph\Models\EventStorage;

/**
 * Class Activate
 */
class Activate
{

    public static function init()
    {
        flush_rewrite_rules();
        EventStorage::getInstance()->initStorageFiles();
    }

}
