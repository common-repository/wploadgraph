<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Dashboard\Pages;

use Tekod\WpLoadGraph\Dashboard\AbstractPage;
use Tekod\WpLoadGraph\Models\EventStorage;


/**
 * Dashboard admin page.
 */
class DashPage extends AbstractPage
{

    // details about menu item
    protected $menuParent = 'tools';

    protected $menuTitle = 'WpLoadGraph';

    protected $pageTitle = 'WpLoadGraph';

    protected $isJQueryPage = true;

    // template to be rendered
    protected $pageTemplate = 'dash-page';

    // maximum number of events to display
    protected $limitData = 100000;

    // internal property
    protected $isLimitedData = false;


    /**
     * Enqueue additional assets.
     */
    protected function includeAssets()
    {
        parent::includeAssets();
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/jquery.datetimepicker.full.js', true);
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/jquery.datetimepicker.min.css');
        // chartjs
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/chart.min.js', true);
        // momentjs
        wp_enqueue_script('moment');
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/chartjs-adapter-moment.min.js', true);
        // hammerjs
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/hammer.min.js', true);
        // zoom
        wploadgraph()->frontend()->enqueueAsset('admin/vendor/chartjs-plugin-zoom.min.js', true);
    }


    /**
     * Setup data that have to be pushed to page template.
     */
    protected function preparePageData(): array
    {
        $from = intval($_GET['wploadgraph_from'] ?? time() - 86400);
        $to = intval($_GET['wploadgraph_to'] ?? time());
        $trace = $this->packTraceData($from, $to);
        $ticks = 0;
        foreach ($trace as $row) {
            $ticks += count($row);
        }
        return [
            'from' => $from,
            'to' => $to,
            'trace' => $trace,
            'limit' => $this->isLimitedData,
            'ticks' => $ticks,
        ];
    }


    /**
     * Prepare data.
     *
     * @param int $from
     * @param int $to
     * @return array
     */
    protected function packTraceData(int $from, int $to): array
    {
        $data = EventStorage::getInstance()->getData($from, $to);
        $data = array_slice($data, 0, $this->limitData);
        $this->isLimitedData = count($data) === $this->limitData;
        $trace = [];
        foreach ($data as $item) {
            $sess = $item['user'];
            $start = floatval($item['ts1']);
            // find available row
            $row = 0;
            while (isset($trace[$sess][$row]) && end($trace[$sess][$row])['ts2'] > $start) {
                $row++;
            }
            $trace[$sess][$row][] = [
                'ts1' => $start,
                'ts2' => floatval($item['ts2']),
                'type' => EventStorage::TRAN_REQUEST_TYPE_TO_NAME[$item['type']],
                'path' => $item['path'],
                'error' => intval($item['error']),
                'mem' => intval($item['mem']),
                'db' => intval($item['db']),
            ];
        }
        return $trace;
    }

}
