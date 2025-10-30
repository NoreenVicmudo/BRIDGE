<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(["error" => "Missing or invalid user id"]);
    exit;
}


$stmt = $con->prepare("
    SELECT user_id, user_firstname AS fname, user_lastname AS lname,
           user_college AS college_id, user_program AS program_id, user_level
    FROM user_account
    WHERE user_id = :id
");
$stmt->execute([":id" => $_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(["error" => "User not found"]);
}
