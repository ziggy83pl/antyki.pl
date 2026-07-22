<?php
declare(strict_types=1);

/* Modified: Secure session and cookie options */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start();

require_once('../../config/config.php');

$admin = new \App\Admin\Admin($db);

if (!$admin->is_logged()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if (empty($_POST['token']) || !checkToken('admin_statistics', $_POST['token'], true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Rate limit
if (!isset($_SESSION['stats_requests'])) $_SESSION['stats_requests'] = [];
$now = time();
$_SESSION['stats_requests'] = array_filter($_SESSION['stats_requests'], fn(int $t): bool => $t > $now - 60);
if (count($_SESSION['stats_requests']) > 30) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}
$_SESSION['stats_requests'][] = $now;

// Validation
$required = ['select_1', 'select_2', 'date_from', 'date_to'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => "Missing: {$f}"]);
        exit;
    }
}

$dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
$dateFrom = $_POST['date_from'];
$dateTo = $_POST['date_to'];

if (!preg_match($dateRegex, (string) $dateFrom) || !preg_match($dateRegex, (string) $dateTo)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

$fromTs = strtotime((string) $dateFrom);
$toTs = strtotime((string) $dateTo);
$todayTs = strtotime(date('Y-m-d'));

if ($fromTs > $todayTs || $toTs > $todayTs || $fromTs > $toTs) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid date range']);
    exit;
}

$daysDiff = (int)(($toTs - $fromTs) / 86400);
if ($daysDiff > 365) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Max 365 days']);
    exit;
}

$allowed = ['logins', 'unique_logins', 'registration', 'activation_users', 'offers', 'views_offers'];
$select1 = $_POST['select_1'];
$select2 = $_POST['select_2'];

if (!in_array($select1, $allowed, true) || !in_array($select2, $allowed, true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid metric']);
    exit;
}

static $queryCache = [];

function fetchMetric(string $metric, string $dateFrom, string $dateTo, PDO $db): array {
    global $queryCache;
    $key = "{$metric}_{$dateFrom}_{$dateTo}";
    if (isset($queryCache[$key])) return $queryCache[$key];

    $from = $dateFrom . ' 00:00:00';
    $to = $dateTo . ' 23:59:59';

    $queries = [
        'logins' => [
            'sql' => 'SELECT DATE(date) as day, COUNT(1) as cnt FROM '._DB_PREFIX_.'logs_user WHERE date >= :from AND date <= :to GROUP BY DATE(date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ],
        'unique_logins' => [
            'sql' => 'SELECT DATE(date) as day, COUNT(DISTINCT user_id) as cnt FROM '._DB_PREFIX_.'logs_user WHERE date >= :from AND date <= :to GROUP BY DATE(date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ],
        'registration' => [
            'sql' => 'SELECT DATE(date) as day, COUNT(1) as cnt FROM '._DB_PREFIX_.'user WHERE date >= :from AND date <= :to GROUP BY DATE(date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ],
        'activation_users' => [
            'sql' => 'SELECT DATE(activation_date) as day, COUNT(1) as cnt FROM '._DB_PREFIX_.'user WHERE activation_date >= :from AND activation_date <= :to GROUP BY DATE(activation_date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ],
        'offers' => [
            'sql' => 'SELECT DATE(date) as day, COUNT(1) as cnt FROM '._DB_PREFIX_.'offer WHERE date >= :from AND date <= :to GROUP BY DATE(date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ],
        'views_offers' => [
            'sql' => 'SELECT DATE(date) as day, COUNT(1) as cnt FROM '._DB_PREFIX_.'logs_offer WHERE date >= :from AND date <= :to GROUP BY DATE(date) ORDER BY day',
            'params' => [':from' => $from, ':to' => $to]
        ]
    ];

    $q = $queries[$metric];
    try {
        $sth = $db->prepare($q['sql']);
        foreach ($q['params'] as $k => $v) $sth->bindValue($k, $v, PDO::PARAM_STR);
        $sth->execute();
        $result = [];
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $result[] = ['x' => $row['day'], 'y' => (int)$row['cnt']];
        }
        $queryCache[$key] = $result;
        return $result;
    } catch (PDOException $e) {
        error_log('Stats query error: ' . $e->getMessage());
        return [];
    }
}

try {
    $data1 = fetchMetric($select1, $dateFrom, $dateTo, $db);
    $data2 = fetchMetric($select2, $dateFrom, $dateTo, $db);

    $allDates = [];
    $current = $fromTs;
    while ($current <= $toTs) {
        $allDates[] = date('Y-m-d', $current);
        $current += 86400;
    }

    $fill = function(array $data, array $dates): array {
        $map = array_column($data, 'y', 'x');
        return array_map(fn($d): array => ['x' => $d, 'y' => $map[$d] ?? 0], $dates);
    };

    // Detect dark mode from request header or default to light colors
    $isDark = isset($_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME']) && $_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME'] === 'dark';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'datasets' => [
            [
                'label' => $select1,
                'data' => $fill($data1, $allDates),
                'borderColor' => '#0d6efd',
                'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 3,
                'pointHoverRadius' => 6
            ],
            [
                'label' => $select2,
                'data' => $fill($data2, $allDates),
                'borderColor' => '#fd7e14',
                'backgroundColor' => 'rgba(253, 126, 20, 0.1)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 3,
                'pointHoverRadius' => 6
            ]
        ],
        'meta' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'total_days' => count($allDates)]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Internal error']);
    error_log('Stats exception: ' . $e->getMessage());
}