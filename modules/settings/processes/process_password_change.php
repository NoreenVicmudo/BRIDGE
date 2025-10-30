<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

$userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
$oldPass = $_POST['old'] ?? '';
$newPass = $_POST['new'] ?? '';
$confirm = $_POST['confirm'] ?? '';


if (!$userId) { 
    echo json_encode(['success'=>'false','msg'=>'Unauthorized']); 
    exit; 
}

// 🔹 Validate inputs
if (empty($oldPass) || empty($newPass) || empty($confirm)) {
    echo json_encode(['success' => false, 'msg' => 'All fields are required.']);
    exit;
}

if ($newPass !== $confirm) {
    echo json_encode(['success' => false, 'msg' => 'New and Confirm Passwords do not match.']);
    exit;
}

// Check if new password is same as current password
if ($newPass === $oldPass) {
    echo json_encode(['success' => false, 'msg' => 'New password cannot be the same as your current password.']);
    exit;
}

if (strlen($newPass) < 8) {
    echo json_encode(['success' => false, 'msg' => 'Password must be at least 8 characters.']);
    exit;
}

// Check for spaces
if (preg_match('/\s/', $newPass)) {
    echo json_encode(['success' => false, 'msg' => 'Password must not contain spaces.']);
    exit;
}

// Check for uppercase letter
if (!preg_match('/[A-Z]/', $newPass)) {
    echo json_encode(['success' => false, 'msg' => 'Password must contain at least one uppercase letter.']);
    exit;
}

// Check for lowercase letter
if (!preg_match('/[a-z]/', $newPass)) {
    echo json_encode(['success' => false, 'msg' => 'Password must contain at least one lowercase letter.']);
    exit;
}

// Check for number
if (!preg_match('/\d/', $newPass)) {
    echo json_encode(['success' => false, 'msg' => 'Password must contain at least one number.']);
    exit;
}

// Check for special character
if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $newPass)) {
    echo json_encode(['success' => false, 'msg' => 'Password must contain at least one special character.']);
    exit;
}

try {
    // fetch old hash
    $stmt = $con->prepare("SELECT user_password FROM user_account WHERE user_id=?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || !password_verify($oldPass, $result['user_password'])) {
        echo json_encode(['success' => false, 'msg' => 'Old password incorrect.']);
        exit;
    }

    // update new pass
    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
    $stmt = $con->prepare("UPDATE user_account 
        SET user_password=?, 
        last_password_change=NOW(), 
        update_reason='password', 
        last_updated_at=NOW(),
        password_reset_attempts_count = 0,
        password_reset_attempts_reset_at = NULL
        WHERE user_id=?");
    $stmt->execute([$newHash, $userId]);

    echo json_encode(['success' => true, 'msg' => 'Password successfully updated']);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>
