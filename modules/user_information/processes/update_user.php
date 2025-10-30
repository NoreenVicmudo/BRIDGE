<?php
session_start();
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Validate required fields
$required_fields = ['id', 'lname', 'fname', 'filterCollege', 'filterPosition'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

$user_id = intval($_POST['id']);
$lname = trim($_POST['lname']);
$fname = trim($_POST['fname']);
$college_id = intval($_POST['filterCollege']);
$position = intval($_POST['filterPosition']);
$program_id = isset($_POST['filterProgram']) && !empty($_POST['filterProgram']) ? intval($_POST['filterProgram']) : null;

// Validate user exists
$check_stmt = $con->prepare("SELECT user_id FROM user_account WHERE user_id = ?");
$check_stmt->execute([$user_id]);
if (!$check_stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Validate college exists
$college_stmt = $con->prepare("SELECT college_id FROM colleges WHERE college_id = ?");
$college_stmt->execute([$college_id]);
if (!$college_stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Invalid college selected']);
    exit;
}

// Validate program if provided (for Program Head position)
if ($position == 3 && $program_id) {
    $program_stmt = $con->prepare("SELECT program_id FROM programs WHERE program_id = ? AND college_id = ?");
    $program_stmt->execute([$program_id, $college_id]);
    if (!$program_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Invalid program selected for this college']);
        exit;
    }
}

try {
    // Start transaction
    $con->beginTransaction();
    
    // Update user information
    $update_sql = "UPDATE user_account SET 
                   user_lastname = ?, 
                   user_firstname = ?, 
                   user_college = ?, 
                   user_level = ?,
                   force_relogin = 1";
    
    $params = [$lname, $fname, $college_id, $position];
    
    // Add program if it's a Program Head position
    if ($position == 3 && $program_id) {
        $update_sql .= ", user_program = ?";
        $params[] = $program_id;
    } else {
        $update_sql .= ", user_program = NULL";
    }
    
    $update_sql .= " WHERE user_id = ?";
    $params[] = $user_id;
    
    $update_stmt = $con->prepare($update_sql);
    $update_stmt->execute($params);
    
    // Commit transaction
    $con->commit();
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
