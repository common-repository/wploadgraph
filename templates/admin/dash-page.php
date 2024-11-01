<?php

declare(strict_types=1);
defined('ABSPATH') || die();
// phpcs:disable Squiz.WhiteSpace.ControlStructureSpacing

$vars = $vars ?? [];

$pointSize = max(16 - floor($vars['ticks'] / 30), 6);
$canvasHeight = max(100 + $vars['ticks'] * $pointSize, 100);

?>
<div class="wrap wploadgraph wploadgraph-dashboard">

    <h1>WP Load Graph</h1>

    <form action="tools.php" method="get">
        <input type="hidden" name="page" value="wploadgraph">
        <div style="float:left;">
            <h3>From:</h3>
            <input type="text" id="wploadgraph_from" name="wploadgraph_from" value="<?php echo esc_attr($vars['from']);?>" />
        </div>
        <div style="float:left; margin-left: 2em;">
            <h3>To:</h3>
            <input type="text" id="wploadgraph_to" name="wploadgraph_to" value="<?php echo esc_attr($vars['to']);?>" />
        </div>
        <div style="float:left; margin: 6em 0 0 2em;">
            <button class="button button-primary">Submit</button>
        </div>
        <div style="clear:both"></div>
    </form>

    <div>
        <h3>Trace:</h3>
        <div class="wploadgraph-canvas-wrap" style="height:<?php echo esc_attr($canvasHeight);?>px;">
            <canvas id="wploadgraphChart"></canvas>
        </div>
    </div>

    <!-- wploadgraph object -->
    <script type='text/javascript'>
        /* <![CDATA[ */
        var WpLoadGraphData = <?php echo wp_json_encode([
            'trace' => $vars['trace'],
            'limit' => $vars['limit'],
            'ticksCount' => $vars['ticks'],
            'pointSize' => $pointSize,
        ]);?>;
        /* ]]> */
    </script>
</div>