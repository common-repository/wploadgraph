<?php

declare(strict_types=1);
namespace Tekod\WpLoadGraph\Models;

/**
 * Class EventStorage.
 * This is classic data model.
 */
class EventStorage
{

    public const REQUEST_TYPE_PAGE = 1;
    public const REQUEST_TYPE_404 = 2;
    public const REQUEST_TYPE_AJAX = 3;
    public const REQUEST_TYPE_REST = 4;
    public const REQUEST_TYPE_CRON = 5;
    public const REQUEST_TYPE_LOGIN = 6;
    public const REQUEST_TYPE_SYSTEM = 7;

    public const TRAN_REQUEST_TYPE_TO_NAME = [
        self::REQUEST_TYPE_PAGE => 'page',
        self::REQUEST_TYPE_404 => '404',
        self::REQUEST_TYPE_AJAX => 'ajax',
        self::REQUEST_TYPE_REST => 'rest',
        self::REQUEST_TYPE_CRON => 'cron',
        self::REQUEST_TYPE_LOGIN => 'login',
        self::REQUEST_TYPE_SYSTEM => 'system',
    ];

    public const FETCH_LIMIT = 5000;

    protected $tracePath = '';  // phpcs:ignore SlevomatCodingStandard.Classes.ClassMemberSpacing

    protected $salt;


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
     * Constructor.
     */
    public function __construct()
    {
        // init properties
        $this->salt = str_repeat(md5(wp_salt(), true), 11); // string of 176 bytes
        $this->tracePath = wp_upload_dir()['basedir'] . '/wploadgraph/' . md5($this->salt) . '.log';
    }


    /**
     * Initialize storage files.
     */
    public function initStorageFiles(): void
    {
        $directory = dirname($this->tracePath);
        // create directory
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        // create protective files
        $htaccess = "$directory/.htaccess";
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\nDeny from all");
        }
        $index = "$directory/index.php";
        if (!is_file($index)) {
            file_put_contents($index, "<?php\n// Silence is golden\n?>");
        }
        // create trace file
        touch($this->tracePath);
    }


    /**
     * Delete directory with storage files.
     */
    public function removeStorageFiles(): void
    {
        global $wp_filesystem;
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        $dir = wp_upload_dir()['basedir'] . '/wploadgraph';
        $wp_filesystem->delete($dir, true, 'd');
    }


    /**
     * Perform some actions on shutdown process.
     */
    public function storeData(array $data)
    {
        global $wpdb;
        $values = [
            'user' => $data['user'],
            'ts1'  => round($data['ts1'], 3),
            'ts2'  => round($data['ts2'], 3),
            'type' => intval($data['type']),
            'error' => $data['error'] ? 1 : 0,
            'mem'  => round(memory_get_peak_usage(true) / 1024 / 1024),
            'db'   => $wpdb->num_queries,
            'path' => str_replace("\t", ' ', $data['path']),  // store path at the end of structure
        ];
        $line = implode("\t", $values);
        file_put_contents($this->tracePath, $this->crypt($line) . "\n", FILE_APPEND);
    }


    /**
     * Retrieve data from storage.
     *
     * @param int $from
     * @param int $to
     * @return array
     */
    public function getData(int $from, int $to): array
    {
        $handle = fopen($this->tracePath, 'rb');
        if ($handle === false) {
            return [];
        }
        $output = [];
        $count = 0;
        while (!feof($handle)) {
            $line = fgets($handle);
            $segments = explode("\t", $this->decrypt(trim((string) $line)));
            if (count($segments) !== 8) {
                continue;
            }
            [$user, $ts1, $ts2, $type, $error, $mem, $db, $path] = $segments;
            if ($ts1 < $from) {
                continue;
            }
            if ($ts1 > $to) {
                break;
            }
            $output[] = [
                'user' => $user,
                'ts1'  => floatval($ts1),
                'ts2'  => floatval($ts2),
                'type' => intval($type),
                'error' => intval($error),
                'mem' => intval($mem),
                'db'  => intval($db),
                'path' => $path,
            ];
            $count++;
            if ($count >= self::FETCH_LIMIT) {
                break;
            }
        }
        return $output;
    }


    /**
     * Crypt string.
     *
     * @param string $text
     * @return string
     */
    protected function crypt(string $text): string
    {
        $text = substr($text, 0, 176);  // limit text on salt size
        return base64_encode($text ^ substr($this->salt, 0, strlen($text)));
    }


    /**
     * Decrypt string.
     *
     * @param string $text
     * @return string
     */
    protected function decrypt(string $text): string
    {
        return base64_decode($text) ^ substr($this->salt, 0, strlen($text));
    }


    /**
     * Reduce size of trace file if it exceeds configured value (kind of log-rotate).
     */
    public function deleteOldData(): void
    {
        $size = is_file($this->tracePath) ? filesize($this->tracePath) : null;
        if ($size < $this->getMaxTraceSize() || $size === null) {
            return;
        }
        // copy file
        $file2 = $this->tracePath . '2';
        $copied = 0;
        $fIn = fopen($this->tracePath, "rb");
        fseek($fIn, -intval($this->getMaxTraceSize() * 0.9), SEEK_END);
        $fOut = fopen($file2, "w");
        while (!feof($fIn)) {
            $buffer = fread($fIn, 2 << 19);
            $copied += fwrite($fOut, $copied ? $buffer : explode("\n", $buffer, 2)[1] ?? '');
        }
        fclose($fIn);
        fclose($fOut);
        // swap files
        unlink($this->tracePath);
        if (!rename($file2, $this->tracePath)) {
            unlink($this->tracePath);  // try again
            rename($file2, $this->tracePath);
        }
    }


    /**
     * Define size limit of trace file.
     * Developer can increase size using hook if necessary.
     *
     * @return int
     */
    public function getMaxTraceSize(): int
    {
        $defaultMaxSize = 200 * 2 << 19;  // 200Mb by default, tested up to 15Gb
        $settings = wploadgraph()->config()->getSettings();
        return apply_filters('wploadgraph-max_trace_size', $settings['MaxTraceFileSize'] ?? $defaultMaxSize);
    }

}
