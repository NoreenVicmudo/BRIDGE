<?php
session_start();
session_unset();
session_destroy();

// Include config.php
require_once __DIR__ . "/config.php";

// Clear cache
header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');

// Check if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit();
}

// Redirect to login page for regular requests
header("Location: " . $protocol . "://" . $host . "/bridge/login");
exit();
