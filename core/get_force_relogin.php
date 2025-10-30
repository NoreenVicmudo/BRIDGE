<?php
session_start();
require_once(__DIR__ . "/j_conn.php"); // adjust path

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    echo json_encode(["success" => false]);
    exit();
}

try {
    $stmt = $con->prepare("
        SELECT force_relogin 
        FROM user_account 
        WHERE user_id = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$_SESSION['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $forceRelogin = ((int)($row['force_relogin'] ?? 0) === 1);

    echo json_encode([
        "success" => true,
        "forceRelogin" => $forceRelogin
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
