<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$code = $_POST['otp'] ?? '';
$newEmail = $_POST['new_email'] ?? null;
$userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId || !$type || !$code) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

try{
    // fetch otp info
    $stmt = $con->prepare("SELECT otp, otp_expiry FROM user_account WHERE user_id=?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['success' => false, 'msg' => 'No user']);
        exit;
    }

    if ($code !== $result['otp']) {
        echo json_encode(['success' => false, 'msg' => 'Invalid OTP']);
        exit;
    }
    if(strtotime($result['otp_expiry']) < time()){
        echo json_encode(['success'=>false,'msg'=>'OTP expired']); exit;
    }

    // ✅ Passed OTP - Reset attempt counter on successful verification
    if($type === 'email' && $newEmail){
        $stmt = $con->prepare("UPDATE user_account 
            SET user_email=?, update_reason='email', last_updated_at=NOW(),
                password_reset_attempts_count = 0,
                password_reset_attempts_reset_at = NULL
            WHERE user_id=?");
        $stmt->execute([$newEmail, $userId]);
        echo json_encode(['success'=>true,'msg'=>'Email updated']);
    }
    elseif($type === 'pass'){
        // Reset attempt counter for password change verification
        $stmt = $con->prepare("UPDATE user_account 
            SET password_reset_attempts_count = 0,
                password_reset_attempts_reset_at = NULL
            WHERE user_id=?");
        $stmt->execute([$userId]);
        echo json_encode(['success'=>true,'msg'=>'Proceed password change']);
    }
    else{
        echo json_encode(['success'=>false,'msg'=>'Unknown type']);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>
