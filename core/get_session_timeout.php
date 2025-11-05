<?php
require_once __DIR__ . "/config.php";
require_once PROJECT_PATH . "/j_conn.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in',
        'timeRemaining' => 0,
        'isExpired' => true
    ]);
    exit();
}

// Check if this is an activity update request (from user interaction) or just a status check
$updateActivity = isset($_GET['update']) && $_GET['update'] === '1';

// Calculate remaining time based on current LAST_ACTIVITY
$timeoutDuration = 300; // 5 minutes
$lastActivity = $_SESSION['LAST_ACTIVITY'] ?? time();
$timeElapsed = time() - $lastActivity;
$timeRemaining = max(0, $timeoutDuration - $timeElapsed);
$isExpired = $timeRemaining <= 0;

// If session is expired, clean it up
if ($isExpired) {
    session_unset();
    session_destroy();
    echo json_encode([
        'success' => false,
        'message' => 'Session expired',
        'timeRemaining' => 0,
        'isExpired' => true,
        'timeoutDuration' => $timeoutDuration,
        'lastActivity' => $lastActivity
    ]);
    exit();
}

// Only update LAST_ACTIVITY if this is an explicit activity update request
// Regular status checks should NOT reset the timer
if ($updateActivity) {
    $_SESSION['LAST_ACTIVITY'] = time();
    $timeRemaining = $timeoutDuration; // Since we just reset it, full time remains
    $lastActivity = $_SESSION['LAST_ACTIVITY'];
}

echo json_encode([
    'success' => true,
    'timeRemaining' => $timeRemaining,
    'isExpired' => false,
    'timeoutDuration' => $timeoutDuration,
    'lastActivity' => $lastActivity
]);
?>
