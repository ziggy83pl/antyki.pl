<?php
/* Template: Load credentials from env or .env file */
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
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

$mysql_server = getenv('DB_HOST') ?: "localhost";
$mysql_user = getenv('DB_USER') ?: "your_database_user";
$mysql_pass = getenv('DB_PASS') ?: "your_database_password";
$mysql_db = getenv('DB_NAME') ?: "your_database_name";

define("_DB_PREFIX_", getenv('DB_PREFIX') !== false ? getenv('DB_PREFIX') : "");
