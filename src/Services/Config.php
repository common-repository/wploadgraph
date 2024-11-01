<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Services;


/**
 * Class Config.
 * This is mandatory service - it is instantiated on each plugin initialization.
 */
class Config
{

    // name of entry in wordpress "options" table
    protected $wpOptionName = 'wploadgraph_config';

    // buffer for loaded configuration
    protected $settings;

    // default structure
    protected $defaultSettings = [
        'MaxTraceFileSize' => 200 * 2 << 19,
    ];


    /**
     * Singleton getter.
     *
     * @return static
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
     * Config constructor.
     */
    public function __construct()
    {
        // load from "options" table
        $settings = get_option($this->wpOptionName, []);

        // resolve and set settings
        $this->settings = $this->resolveSettings($settings);
    }


    /**
     * Return loaded configuration.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }


    /**
     * Set current configuration.
     *
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }


    /**
     * Validate each parts of settings.
     *
     * @param array|string $settings
     * @return array
     */
    protected function resolveSettings($settings): array  // phpcs:ignore  Inpsyde.CodeQuality.ArgumentTypeDeclaration -- mixed
    {
        // just in case if some string is stored
        if (is_string($settings)) {
            $settings = @unserialize($settings);  // phpcs:ignore Generic.PHP.NoSilencedErrors -- unserialize cannot be predicted
        }

        // ensure array type
        if (!is_array($settings)) {
            $settings = [];
        }

        // add missing keys
        $settings += $this->defaultSettings;

        // perform deeper resolving if needed
        // ...

        // return structure
        return $settings;
    }


    /**
     * Store current configuration in database.
     */
    public function saveConfig()
    {
        update_option($this->wpOptionName, $this->settings, true);
    }

}
