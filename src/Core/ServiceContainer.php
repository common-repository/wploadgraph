<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Core;

use Tekod\WpLoadGraph\Models\Shutdown;
use Tekod\WpLoadGraph\Services\Config;
use Tekod\WpLoadGraph\Services\Frontend;


/**
 * Class ServiceContainer.
 * This is plugin service locator (servicer), it will be exposed via wploadgraph() global function.
 */
class ServiceContainer
{

    // validation check result
    protected $requirementsMet = false;


    /**
     * Singleton getter.
     *
     * @return ServiceContainer
     */
    public static function getInstance(): self
    {
        static $instance;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }


    /**
     * Initialize.
     *
     * @param array  list of unsatisfied requirements
     */
    public function init(array $requirements)
    {
        // save value
        $this->requirementsMet = $requirements;

        // load frontend to trigger hooks
        Frontend::getInstance();
    }


    /**
     * Return validation check result.
     *
     * @return bool
     */
    public function requirementsMet(): bool
    {
        return $this->requirementsMet;
    }


    /**
     * Public getter of data model.
     *
     * @return Shutdown
     */
    public function model(): Shutdown
    {
        // replace with real model class
        return Shutdown::getInstance();
    }


    /**
     * Public getter of config registry.
     *
     * @return Config
     */
    public function config(): Config
    {
        return Config::getInstance();
    }


    /**
     * Public getter of Frontend service.
     *
     * @return Frontend
     */
    public function frontend(): Frontend
    {
        return Frontend::getInstance();
    }


    /**
     * Shorthand method for retrieving template and echoing it.
     *
     * @param string $path
     * @param array|null   $vars
     */
    public function template(string $path, ?array $vars = null)
    {
        $this->frontend()->renderTemplate($path, $vars);
    }

}
