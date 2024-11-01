<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Models;

/**
 * Class Shutdown.
 * This is classic data model.
 */
class Shutdown
{

    /**
     * Singleton getter.
     *
     * @return self
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
     * Schedule storing event on end of current request.
     */
    public static function register()
    {
        register_shutdown_function([self::getInstance(), 'onShutdown']);
    }


    /**
     * Constructor.
     */
    public function __construct()
    {
        //..
    }


    /**
     * Perform some actions on shutdown process.
     */
    public function onShutdown(): void
    {
        // store event into database
        $data =  [
            'user'  => $this->getCurrentSessionId(),
            'ts1'   => $this->getStartTime(),
            'ts2'   => microtime(true),
            'type'  => $this->getCurrentRequestType(),
            'error' => $this->isError(),
            'path'  => $this->getCurrentPath(),
        ];
        EventStorage::getInstance()->storeData($data);
    }


    /**
     * Return unique session identifier.
     *
     * @return string
     */
    protected function getCurrentSessionId(): string
    {
        // for logged-in visitor
        $user = wp_get_current_user();
        if ($user->ID > 0) {
            return "user:#$user->ID($user->user_login)";
        }
        // for anonymous visitor
        $browser = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '-');
        $ipAddress = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '-');
        return '~' . substr(md5("$browser-$ipAddress"), 0, 16);
    }


    /**
     * Return timestamp of beginning of current http request.
     *
     * @return float
     */
    protected function getStartTime(): float
    {
        $time = floatval($_SERVER['REQUEST_TIME_FLOAT'] ?? 0);
        return $time;
    }


    /**
     * Return type of current request.
     *
     * @return int
     */
    protected function getCurrentRequestType(): int
    {
        global $pagenow;
        $restUrl = wp_parse_url(trailingslashit(rest_url()));
        $url = wp_parse_url(add_query_arg([]));
        $urlPath = $url['path'] ?? '/';
        // CRON:
        if (wp_doing_cron()) {
            return EventStorage::REQUEST_TYPE_CRON;
        }
        // REST:
        if (
            defined('REST_REQUEST') && REST_REQUEST
            || strpos(sanitize_text_field($_GET['rest_route'] ?? ''), '/') === 0  // support "plain" permalink settings and `rest_route` starts with `/`
            || strpos($urlPath, $restUrl['path']) === 0  // path begins with "wp-json/"
        ) {
            return EventStorage::REQUEST_TYPE_REST;
        }
        // SYSTEM:
        if (
            (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
            || in_array($pagenow, ['wp-activate.php', 'wp-comments-post.php', 'wp-mail.php', 'wp-links-opml.php', 'wp-trackback.php'], true)
        ) {
            return EventStorage::REQUEST_TYPE_SYSTEM;
        }
        // LOGIN
        if (in_array($pagenow, ['wp-signup.php', 'wp-login.php'], true)) {
            return EventStorage::REQUEST_TYPE_LOGIN;
        }
        // AJAX:
        if (wp_is_json_request() || wp_doing_ajax()) {
            return EventStorage::REQUEST_TYPE_AJAX;
        }
        // everything else is a page
        return is_404()
            ? EventStorage::REQUEST_TYPE_404
            : EventStorage::REQUEST_TYPE_PAGE;
    }


    /**
     * Return path of current request.
     *
     * @return string
     */
    protected function getCurrentPath(): string
    {
        $path = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '-');
        $action = wp_is_json_request() || wp_doing_ajax() ? sanitize_text_field($_POST['action'] ?? '') : '';
        return $action ? "$path ($action)" : $path;
    }


    /**
     * Check is current request finished with "fatal error".
     *
     * @return bool
     */
    protected function isError(): bool
    {
        $err = error_get_last();
        return $err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    }

}
