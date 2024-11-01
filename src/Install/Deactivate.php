<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Install;

/**
 * Class Deactivate
 */
class Deactivate
{

    public static function init()
    {
        flush_rewrite_rules();
    }

}
