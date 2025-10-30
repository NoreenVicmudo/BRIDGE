<?php
require_once("j_conn.php"); // adjust to your DB connection file

header('Content-Type: application/json');

// Get data from the POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['level'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$newUsername = $data['username'];
$newLevel = $data['level'];
$userId = $_SESSION['id']; 

try {
    // 1. Update the database
    $stmt = $con->prepare("UPDATE user_account SET user_username = ?, user_level = ? WHERE user_id = ?");
    $stmt->execute([$newUsername, $newLevel, $userId]);

    // 2. Update the session variables
    $_SESSION['username'] = $newUsername;
    $_SESSION['level'] = $newLevel;
    
    // Send a success response
    echo json_encode(['success' => true, 'new_username' => $newUsername, 'new_level' => $newLevel]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $e->getMessage()]);
}
?>