<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== Giełda Budowlana Debugger ===\n\n";

echo "PHP Version: " . php_sapi_name() . " / " . PHP_VERSION . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script path: " . __FILE__ . "\n\n";

$configPath = 'config/config.php';
echo "Checking if config/config.php exists: " . (file_exists($configPath) ? "YES" : "NO") . "\n";
if (file_exists($configPath)) {
    echo "config/config.php size: " . filesize($configPath) . " bytes\n";
    echo "config/config.php modified: " . date("Y-m-d H:i:s", filemtime($configPath)) . "\n";
}

$dbConfigPath = 'config/db.php';
echo "Checking if config/db.php exists: " . (file_exists($dbConfigPath) ? "YES" : "NO") . "\n";
if (file_exists($dbConfigPath)) {
    echo "config/db.php size: " . filesize($dbConfigPath) . " bytes\n";
}

echo "\n--- Including config/config.php ---\n";
try {
    require_once($configPath);
    echo "Included successfully!\n";
} catch (Throwable $e) {
    echo "ERROR during include: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n--- Checking Global Variables ---\n";
echo "Is \$db defined? " . (isset($db) ? "YES" : "NO") . "\n";
if (isset($db)) {
    echo "Is \$db a PDO instance? " . ($db instanceof PDO ? "YES" : "NO") . "\n";
    if ($db instanceof PDO) {
        try {
            $stmt = $db->query("SELECT 1");
            echo "Database connection test query: OK\n";
        } catch (Throwable $e) {
            echo "Database query failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "Is \$settings defined? " . (isset($settings) ? "YES" : "NO") . "\n";
if (isset($settings)) {
    echo "Settings count: " . count($settings) . "\n";
    echo "base_url: " . ($settings['base_url'] ?? 'NOT SET') . "\n";
    echo "scraper_mylomza_enabled: " . ($settings['scraper_mylomza_enabled'] ?? 'NOT SET') . "\n";
}

echo "\n--- Checking App Context ---\n";
try {
    $appDb = \App\Core\App::db();
    echo "App::db() is initialized: YES\n";
} catch (Throwable $e) {
    echo "App::db() error: " . $e->getMessage() . "\n";
}

try {
    $appSettings = \App\Core\App::settings();
    echo "App::settings() count: " . count($appSettings) . "\n";
    echo "App::settings()['base_url']: " . ($appSettings['base_url'] ?? 'NOT SET') . "\n";
} catch (Throwable $e) {
    echo "App::settings() error: " . $e->getMessage() . "\n";
}
