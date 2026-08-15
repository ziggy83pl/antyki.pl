<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;
use HTMLPurifier;

class App {
    private static ?PDO $db = null;
    private static array $settings = [];
    private static ?HTMLPurifier $purifier = null;
    
    public static function setDb(PDO $db): void {
        self::$db = $db;
    }
    
    public static function db(): PDO {
        if (self::$db === null) {
            throw new Exception("Database connection has not been initialized in App context.");
        }
        return self::$db;
    }

    public static function setSettings(array $settings): void {
        self::$settings = $settings;
    }

    public static function settings(): array {
        return self::$settings;
    }
    
    public static function setting(string $key, $default = null) {
        return self::$settings[$key] ?? $default;
    }

    public static function setPurifier(HTMLPurifier $purifier): void {
        self::$purifier = $purifier;
    }
    
    public static function purifier(): HTMLPurifier {
        if (self::$purifier === null) {
            $purifier_config = \HTMLPurifier_Config::createDefault();
            $purifier_config->set('HTML.SafeIframe', true);
            $purifier_config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'); //allow YouTube and Vimeo
            self::$purifier = new HTMLPurifier($purifier_config);
        }
        return self::$purifier;
    }

    public static function ensureLogsAuthTable(): void {
        try {
            $db = self::db();
            $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."logs_auth` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `ip` varchar(45) NOT NULL,
                `action_type` varchar(32) NOT NULL,
                `status` varchar(16) NOT NULL,
                `identifier` varchar(255) DEFAULT '',
                `details` text DEFAULT NULL,
                `user_agent` varchar(255) DEFAULT '',
                `date` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ip` (`ip`),
                KEY `idx_action` (`action_type`),
                KEY `idx_status` (`status`),
                KEY `idx_date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
    }

    public static function logAuth(string $actionType, string $status, string $identifier = '', string $details = ''): void {
        try {
            self::ensureLogsAuthTable();
            $db = self::db();
            $ip = function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '');
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'logs_auth` (`ip`, `action_type`, `status`, `identifier`, `details`, `user_agent`, `date`) VALUES (:ip, :action_type, :status, :identifier, :details, :user_agent, NOW())');
            $sth->execute([
                ':ip' => $ip,
                ':action_type' => $actionType,
                ':status' => $status,
                ':identifier' => substr($identifier, 0, 255),
                ':details' => $details,
                ':user_agent' => $userAgent
            ]);
        } catch (\Throwable $e) {}
    }
}
