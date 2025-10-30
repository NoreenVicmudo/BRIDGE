<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../core/phpmailer/src/Exception.php';
require '../../../core/phpmailer/src/PHPMailer.php';
require '../../../core/phpmailer/src/SMTP.php';

$type = $_POST['type'] ?? ''; // "email" or "pass"
$newEmail = $_POST['new_email'] ?? null;
$userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if(!$userId || !$type){
    echo json_encode(['success'=>false,'msg'=>'Unauthorized']);
    exit;
}

try {
    // ✅ Check attempt limits first (shared for both email and password changes)
    try {
        $stmt = $con->prepare("SELECT password_reset_attempts_count, password_reset_attempts_reset_at 
            FROM user_account WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rateLimitData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rateLimitData) {
            $attempts = $rateLimitData['password_reset_attempts_count'];
            $resetAt = $rateLimitData['password_reset_attempts_reset_at'];
            
            // Reset counter if it's been more than 1 hour
            if ($resetAt && strtotime($resetAt) < strtotime('-1 hour')) {
                // Actually reset the counter in the database
                $resetStmt = $con->prepare("UPDATE user_account 
                    SET password_reset_attempts_count = 0 
                    WHERE user_id = ?");
                $resetStmt->execute([$userId]);
                $attempts = 0;
            }
            
            if ($attempts >= 3) {
                echo json_encode(['success' => false, 'msg' => 'Too many reset attempts. Please try again in 1 hour.']);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Rate limiting check failed: " . $e->getMessage());
    }

    // ✅ Extra checks only if type=email and newEmail is given
    if ($type === "email" && $newEmail) {
        $newEmail = trim($newEmail);

        /*
        // 1. Only Gmail allowed
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $newEmail)) {
            echo json_encode(['success' => false, 'msg' => 'Only Gmail addresses are allowed']);
            exit;
        }*/

        // 2. Prevent using the same email as current
        $stmt = $con->prepare("SELECT user_email FROM user_account WHERE user_id=?");
        $stmt->execute([$userId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current && strtolower($current['user_email']) === strtolower($newEmail)) {
            echo json_encode(['success' => false, 'msg' => 'Email currently used']);
            exit;
        }

        // 3. Prevent using an email already in DB
        $stmt = $con->prepare("SELECT COUNT(*) FROM user_account WHERE user_email=?");
        $stmt->execute([$newEmail]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            echo json_encode(['success' => false, 'msg' => 'This email is already used']);
            exit;
        }
    }

    // ✅ Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date("Y-m-d H:i:s", time() + 120); // 2 mins

    // Save OTP
    $stmt = $con->prepare("UPDATE user_account SET otp=?, otp_expiry=? WHERE user_id=?");
    $stmt->execute([$otp, $expiry, $userId]);

    // Decide recipient
    if ($type === "email" && $newEmail) {
        $toEmail = $newEmail;
    } else {
        $stmt = $con->prepare("SELECT user_email FROM user_account WHERE user_id=?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $toEmail = $row['user_email'] ?? null;
    }

    if (!$toEmail) {
        echo json_encode(['success' => false, 'msg' => 'No email found']);
        exit;
    }

    // ====== Send Email ======
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "bridge.mcu@gmail.com"; 
    $mail->Password = "pmpj kqij isdi jtwl"; 
    $mail->SMTPSecure = "ssl";
    $mail->Port = 465;

    $mail->setFrom('bridge.mcu@gmail.com', 'MCU Bridge');
    $mail->addAddress($toEmail);
    $mail->isHTML(true);
    $mail->Subject = "OTP Request";
    $mail->Body = "We received a request to reset your $type.<br><br>
                    Your OTP code is: <b>$otp</b> <br><br>
                    This code will expire in 2 minutes.<br><br>
                    If you did not request a reset, ignore this email.";
    $mail->AltBody = "We received a request to reset your $type.\n\n
                        Your OTP code is: $otp\n\n
                        This code will expire in 2 minutes.\n\n
                        If you did not request a reset, ignore this email.";

    if ($mail->send()) {
        // ✅ INCREMENT RESET ATTEMPTS COUNTER (only when OTP is successfully sent)
        try {
            $stmt = $con->prepare("UPDATE user_account 
                SET password_reset_attempts_count = password_reset_attempts_count + 1,
                    password_reset_attempts_reset_at = NOW()
                WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Failed to update reset attempts: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'msg' => 'OTP sent']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Mailer failed']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
    exit;
}