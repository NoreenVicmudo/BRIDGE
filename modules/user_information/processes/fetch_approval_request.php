<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

try {
    $sql = "SELECT 
                ua.user_id,
                ua.user_username,
                ua.user_firstname,
                ua.user_lastname,
                c.name AS college_name,
                CASE ua.user_level
                    WHEN 0 THEN 'Admin'
                    WHEN 1 THEN 'Dean'
                    WHEN 2 THEN 'Administrative Assistant'
                    WHEN 3 THEN 'Program Head'
                    ELSE 'Unknown'
                END AS position,
                ua.signup_completed_at  -- add this
            FROM user_account ua
            LEFT JOIN colleges c ON ua.user_college = c.college_id
            WHERE ua.is_active = 0
            AND ua.signup_completed_at IS NOT NULL";


    $stmt = $con->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "data" => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
