<?php
/* Modified: Load credentials from env or .env file */
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }
            if (function_exists('putenv')) {
                @putenv("$key=$value");
            }
            $_ENV[$key] = $value;
        }
    }
}

$host = $_SERVER['HTTP_HOST'] ?? '';
$host_only = explode(':', $host)[0];
$is_local = in_array($host_only, ['localhost', '127.0.0.1']) || str_ends_with($host_only, '.local');

if ($is_local || (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST']))) {
    // Ustawienia dla środowiska lokalnego (Docker / XAMPP)
    $mysql_server = $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: "db");
    $mysql_user = $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: "dev");
    $mysql_pass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: "dev");
    $mysql_db = $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: "cms");
    define("_DB_PREFIX_", $_ENV['DB_PREFIX'] ?? (getenv('DB_PREFIX') !== false ? getenv('DB_PREFIX') : ""));
} else {
    // Ustawienia dla środowiska produkcyjnego (InfinityFree)
    $mysql_server = "sql200.infinityfree.com";
    $mysql_user = "if0_42474242";
    $mysql_pass = "BBQRGU4cNon";
    $mysql_db = "if0_42474242_antyki";
    
    // Zapobiegaj redefinicji stałej, jeśli przypadkiem by to nastąpiło
    if (!defined("_DB_PREFIX_")) {
        define("_DB_PREFIX_", "");
    }
}

