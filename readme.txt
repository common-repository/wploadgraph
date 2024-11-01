=== WpLoadGraph - Log and display server load of your WP site ===
Contributors: tekod
Tags: stress test, performance, debug, server, cron
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 0.2.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stress testing tool for logging and measuring all requests to your WordPress website and displaying in timeline format.

== Description ==
This plugin will track all incoming requests to your server that triggers loading WordPress core:
- regular pages
- 404 page
- login, register and lost-password pages
- ajax, rest & xmlrpc requests
- cron requests

Somewhat similar to "access log" feature most servers already has,
but with one important improvement - it stores how long each process executes!
That information is essential for analyzing stress test results.

Now we can visualize what requests was ran in parallel with other requests, competing for resources of the same CPU.
Now you can see are your pages loading so slow because there is cronjob working in background.


==Usage==
Simply install and activate plugin. No settings are available.

Plugin will add new menu item in admin dashboard, in "Tools" menu, sub-page "WpLoadGraph".
It has nicely styled timeline graph and filter to specify period of time you interested in.

Requests are grouped by "session id" to make visual analysing easier, and coloured according to their type.
Graph has "zoom" ability (use mouse wheel to zoom in and zoom out) and "pan" ability (mouse drag left and right).

There is a limitation of javascript library used for displaying events - it can contain maximum of 5000 elements,
so only first 5000 entries will be shown in graph if you selected too wide range in filter.

To avoid storing too large log file plugin will periodically check it size and strip off the oldest entries to keep it in reasonable size.
By default, that limit is 200Mb, but can be modified using filter hook "wploadgraph-max_trace_size".

==Contact==
    
Please, send bug reports and feature requests to <a href="mailto:office@tekod.com">office@tekod.com</a>

