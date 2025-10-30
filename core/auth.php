<?php
// 🔐 Session and Cookie Configuration
$is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $is_https,  // false for local, true for when hosted
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require_once __DIR__ . "/config.php";

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Absolute login URL (ensures redirect always goes to bridge/)
$loginURL = $protocol . "://" . $host . "/bridge/login";


// --------------------
// 🕒 Auto logout after inactivity
// --------------------
$timeoutDuration = 300; // 5 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {
    // Session expired - show toast then redirect
    session_unset();
    session_destroy();
    
    header("Location: " . $protocol . "://" . $host . "/bridge/session-expired?timeout=1");
    exit();
}

// Update activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();

// --------------------
// Check if session exists
// --------------------
if (empty($_SESSION['id'])) {
    header("Location: " . $loginURL);
    exit();
}

// --------------------
// Force relogin logic
// --------------------
try {
    // Include DB connection if not already
    if (!isset($con)) {
        require_once PROJECT_PATH . "/j_conn.php";
    }

    // Fetch DB values
    $stmt = $con->prepare("
        SELECT user_level, user_college, user_program, force_relogin
        FROM user_account
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$_SESSION['id']]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user missing or force relogin flagged -> logout
    if (!$dbUser || ((int)($dbUser['force_relogin'] ?? 0) === 1)) {
        session_unset();
        session_destroy();
        header("Location: " . $loginURL);
        exit();
    }

    // Compare session vs DB for critical access values
    $mismatch = false;
    if (isset($_SESSION['level']) && (string)$_SESSION['level'] !== (string)$dbUser['user_level']) $mismatch = true;
    if (isset($_SESSION['college']) && (string)$_SESSION['college'] !== (string)$dbUser['user_college']) $mismatch = true;
    if (isset($_SESSION['program']) && (string)$_SESSION['program'] !== (string)$dbUser['user_program']) $mismatch = true;

    if ($mismatch) {
        session_unset();
        session_destroy();
        header("Location: " . $loginURL);
        exit();
    }

    // Keep session values consistent with DB
    $_SESSION['level'] = $dbUser['user_level'];
    $_SESSION['college'] = $dbUser['user_college'];
    $_SESSION['program'] = $dbUser['user_program'];

} catch (Exception $e) {
    // On DB error, fail safe: logout
    session_unset();
    session_destroy();
    header("Location: " . $loginURL);
    exit();
}