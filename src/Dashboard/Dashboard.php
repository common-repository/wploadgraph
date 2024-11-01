<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Dashboard;

use Tekod\WpLoadGraph\Dashboard\Pages\DashPage;
use Tekod\WpLoadGraph\Install\Installer;


/**
 * Class Dashboard
 */
class Dashboard
{

    // transient key for admin notices
    protected $adminMsgTransient = 'wploadgraph_admin_notice';

    // classes of admin pages
    protected $adminPages = [
        DashPage::class,
    ];


    /**
     * Initialization.
     */
    public static function init()
    {
        // instantiate object
        $dashboard = new static();

        // hooks
        add_action('wp_loaded', [$dashboard, 'onLoaded']);
        add_filter('plugin_action_links_' . WPLOADGRAPH_PLUGINBASENAME, [$dashboard, 'pluginSubLinks']);
        add_filter('plugin_row_meta', [$dashboard, 'pluginMetaLinks'], 10, 2);

        // init admin pages
        $dashboard->registerAdminPages();
    }


    /**
     * Initialize all admin pages.
     */
    protected function registerAdminPages()
    {
        // each page
        foreach ($this->adminPages as $class) {
            $class::init($this);
        }
    }


    /**
     * Insert custom content at "plugins" page in area under plugin name.
     *
     * @param array $links
     * @return array
     */
    public function pluginSubLinks(array $links): array
    {
        return $links;
    }


    /**
     * Insert custom links at "plugins" page in area under plugin description.
     *
     * @param array  $links
     * @param string $file
     * @return array
     */
    public function pluginMetaLinks(array $links, string $file): array
    {
        //if ($File === WPLOADGRAPH_PLUGINBASENAME) {
        //    $Links['donate']= '<a href="https://tekod.com/donate/" target="_blank">Donate</a>';
        //    $Links['hireme']= '<a href="https://tekod.com/portfolio/" target="_blank">Hire Me For A Project</a>';
        //}
        return $links;
    }


    /**
     * Prepare content at bottom-left of admin page.
     *
     * @return string
     */
    public function renderFooterLeft(): string
    {
        /* translators: %s: Public plugin link. */
        $message = __('If you like this plugin, please leave a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating to support continued development.', 'wploadgraph');
        $link = 'https://wordpress.org/support/plugin/wploadgraph/reviews/?rate=5#new-post';
        return sprintf($message, $link);
    }


    /**
     * Prepare content at bottom-right of admin page.
     *
     * @return string
     */
    public function renderFooterRight(): string
    {
        return 'Plugin version: ' . WPLOADGRAPH_VERSION;
    }


    /**
     * Perform installation check.
     * Retrieves version number form database and apply updates if needed.
     */
    public function onLoaded(): void
    {
        // install database tables if needed
        // TODO: reconsider is it good idea to do this check only on admin side
        if (!wp_doing_ajax()) {
            (new Installer())->checkDatabase();
        }
    }


    /**
     * Schedule rendering admin notices.
     */
    public function prepareAdminNotices(): void
    {
        add_action('admin_notices', [$this, 'renderAdminNotice']);
    }


    /**
     * Set admin notice message.
     *
     * @param string|bool $success
     * @param string      $message
     */
    public function setAdminNotice($success, $message): void  // phpcs:ignore Inpsyde.CodeQuality.ArgumentTypeDeclaration -- mixed
    {
        $class = is_string($success)
            ? $success
            : ($success ? 'updated' : 'error');
        set_transient($this->adminMsgTransient . '_msg', "$class-$message");
    }


    /**
     * Display notices on top of admin page.
     */
    public function renderAdminNotice(): void
    {
        $message = get_transient($this->adminMsgTransient . '_msg');
        delete_transient($this->adminMsgTransient . '_msg');

        $parts = explode('-', $message ?: '', 2);
        if (count($parts) === 2) {
            echo '<div class="' . esc_attr($parts[0]) . '"><p>' . esc_html($parts[1]) . '</p></div>';
        }
    }

}
