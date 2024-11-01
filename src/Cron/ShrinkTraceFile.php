<?php
declare(strict_types=1);

namespace Tekod\WpLoadGraph\Cron;


use Tekod\WpLoadGraph\Models\EventStorage;

/**
 * Cron-job class.
 */
class ShrinkTraceFile
{

    // time delay for first cron job execution, in seconds, zero to run immediately
    protected $delay = 0;

    // debug mode
    protected $debug = false;

    // internal properties
    protected $class;

    protected $cronHookName;

    protected $instanceId;


    /**
     * Initializer.
     */
    public static function init()
    {
        new static();
    }


    /**
     * Protected constructor.
     */
    protected function __construct()
    {
        // resolve dynamic class name
        $this->class = get_class($this);

        // prepare unique names
        $this->cronHookName = $this->class . '-CronHook';

        // prepare unique string
        $this->instanceId = substr(str_replace(['+', '/'], '', base64_encode(md5($this->class . wp_rand(), true))), 0, 6);

        // register our cron handler
        $this->scheduleCronJob();
    }


    /**
     * Register our cron job handler.
     */
    protected function scheduleCronJob()
    {
        // in debug mode this cron will be executed on EACH page opening
        if ($this->debug) {
            $timestamp = wp_next_scheduled($this->cronHookName);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $this->cronHookName);
            }
        }

        // don't schedule if already scheduled
        if (!wp_next_scheduled($this->cronHookName)) {
            wp_schedule_event(time() + $this->delay, 'daily', $this->cronHookName);
        }

        // register CRON hook action
        add_action($this->cronHookName, [$this, 'onCron']);
    }


    /**
     * Cron job handler.
     */
    public function onCron(): void
    {
        $this->removeOldEntries();
    }


    /**
     * Shrink trace file by deleting oldest entries
     */
    protected function removeOldEntries(): void
    {
        EventStorage::getInstance()->deleteOldData();
    }

}
