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
}
