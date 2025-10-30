<?php
session_start();
include '../../core/j_conn.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';

    $stmt = $con->prepare("SELECT otp_expiry FROM user_account WHERE reset_token = :token AND reset_purpose = 'reset_password'");
    $stmt->execute([":token" => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $expiry = strtotime($row['otp_expiry']);
        echo json_encode([
            "valid" => $expiry > time(),
            "expiry" => $expiry * 1000
        ]);
    } else {
        echo json_encode(["valid" => false]);
    }
}
