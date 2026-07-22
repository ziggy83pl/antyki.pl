<?php

/* Modified: Secure session options */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');

session_start();

require_once('../config/config.php');

// Check authentication
$user = new \App\User();
if (!$user->logged_in) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'info' => 'Not authenticated']);
    exit;
}

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$user_id = (int)$user->id;

if ($room_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'info' => 'Invalid room_id']);
    exit;
}

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}
ob_implicit_flush(true);

// Close session to avoid blocking other requests
session_write_close();

$max_iterations = 60;  // 2 minutes max (60 * 2s)
$heartbeat_interval = 25; // seconds
$check_interval = 2;   // seconds between DB checks
$last_heartbeat = time();

for ($i = 0; $i < $max_iterations; $i++) {
    // Check connection
    if (connection_aborted()) {
        break;
    }

    // Send heartbeat every 25 seconds
    if (time() - $last_heartbeat >= $heartbeat_interval) {
        echo ": heartbeat\n\n";
        $last_heartbeat = time();
    }

    // Check for new messages
    $new_messages = \App\Chat::getNewMessages($room_id, $user_id, $last_id);

    if (!empty($new_messages)) {
        // Remove empty state placeholder
        $payload = json_encode(['status' => true, 'messages' => $new_messages]);
        echo "data: {$payload}\n\n";

        // Update last_id to the most recent message
        $last_id = (int)end($new_messages)['id'];
    }

    // Sleep for check interval
    sleep($check_interval);
}

// Signal client to reconnect naturally
echo "data: {\"status\":false,\"info\":\"stream_end\"}\n\n";
