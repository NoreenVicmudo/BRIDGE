<?php
header("Content-Type: application/json");
session_start();
include '../../core/j_conn.php';

require '../../core/phpmailer/src/Exception.php';
require '../../core/phpmailer/src/PHPMailer.php';
require '../../core/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP($length = 6) {
    $otp = random_int(0, pow(10, $length) - 1);
    return str_pad($otp, $length, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token'] ?? '');

    if (empty($token)) {
        echo json_encode(["success" => false, "message" => "Invalid token"]);
        exit;
    }

    try {
        $stmt = $con->prepare("SELECT user_email FROM user_account WHERE reset_token = :token AND reset_expiry > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Token not found or expired. Please click back to generate OTP again."]);
            exit;
        }

        $email = $user['user_email'];

        // ✅ RATE LIMITING - Check attempts using existing table
        try {
            $stmt = $con->prepare("SELECT password_reset_attempts_count, password_reset_attempts_reset_at 
                FROM user_account WHERE user_email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $rateLimitData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rateLimitData) {
                $attempts = $rateLimitData['password_reset_attempts_count'];
                $resetAt = $rateLimitData['password_reset_attempts_reset_at'];
                
                // Reset counter if it's been more than 1 hour
                if ($resetAt && strtotime($resetAt) < strtotime('-1 hour')) {
                    // Actually reset the counter in the database
                    $resetStmt = $con->prepare("UPDATE user_account 
                        SET password_reset_attempts_count = 0 
                        WHERE user_email = :email");
                    $resetStmt->bindParam(':email', $email);
                    $resetStmt->execute();
                    $attempts = 0;
                }
                
                if ($attempts >= 3) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Too many reset attempts. Please try again in 1 hour."
                    ]);
                    exit();
                }
            }
        } catch (Exception $e) {
            error_log("Rate limiting check failed: " . $e->getMessage());
        }

        $otp = generateOTP();
        $expiryTime = date("Y-m-d H:i:s", strtotime("+2 minutes"));

        $update = $con->prepare("UPDATE user_account SET otp = :otp, otp_expiry = :expiry WHERE reset_token = :token");
        $update->bindParam(':otp', $otp);
        $update->bindParam(':expiry', $expiryTime);
        $update->bindParam(':token', $token);
        $update->execute();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bridge.mcu@gmail.com';
        $mail->Password = 'pmpj kqij isdi jtwl';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('bridge.mcu@gmail.com', 'MCU Bridge');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password';
        $mail->Body = "Your new OTP is: <b>$otp</b> <br><br>This code expires in 2 minutes.";

        $mail->send();

        // ✅ INCREMENT RESET ATTEMPTS COUNTER
        try {
            $stmt = $con->prepare("UPDATE user_account 
                SET password_reset_attempts_count = password_reset_attempts_count + 1,
                    password_reset_attempts_reset_at = NOW()
                WHERE user_email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update reset attempts: " . $e->getMessage());
        }

        // Send expiry as JavaScript timestamp (ms since epoch)
        echo json_encode([
            "success" => true,
            "message" => "OTP resent successfully",
            "expiry"  => strtotime($expiryTime) * 1000 
        ]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Server error"]);
    }
}
