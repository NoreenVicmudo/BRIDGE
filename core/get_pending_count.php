<?php
require_once("j_conn.php"); // adjust to your DB connection file

try {
    $stmt = $con->prepare("
        SELECT COUNT(*) as cnt 
        FROM user_account 
        WHERE is_active = 0 
        AND signup_completed_at IS NOT NULL
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $count = (int)$row['cnt'];

    // cap at 99+
    if ($count > 99) {
        $countText = "99+";
    } else {
        $countText = $count;
    }

    echo json_encode([
        "success" => true,
        "count" => $count,
        "display" => $countText
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
