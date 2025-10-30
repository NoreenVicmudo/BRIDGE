<?php
session_start();
include '../../core/j_conn.php'; // PDO $con

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../core/phpmailer/src/Exception.php';
require '../../core/phpmailer/src/PHPMailer.php';
require '../../core/phpmailer/src/SMTP.php';

function generateOTP($length = 6) {
    $otp = random_int(0, pow(10, $length) - 1);
    return str_pad($otp, $length, '0', STR_PAD_LEFT);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    try {
        //Check attempts using existing table
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
        
        $stmt = $con->prepare("SELECT user_id, user_firstname, user_lastname, is_active FROM user_account WHERE user_email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['is_active'] != 1) { 
                // Assuming 1 = active, 0 = pending
                echo json_encode([
                    "success" => false,
                    "message" => "Your account is not yet active. Please wait for approval."
                ]);
                exit();
            }

            // ✅ Check if OTP is still valid for this email
            $stmt = $con->prepare("SELECT reset_token, otp_expiry FROM user_account WHERE user_email = :email");
            $stmt->execute([":email" => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && strtotime($row['otp_expiry']) > time()) {
                echo json_encode([
                    "success" => true,
                    "redirect" => "verify-account?token=" . urlencode($row['reset_token']) . "&newotp=0",
                    "email" => $email,
                    "expiry" => strtotime($row['otp_expiry']) * 1000 // <-- send ms timestamp
                ]);
                exit();
            }

            // Generate OTP + expiry
            $otp = generateOTP();
            $expiryTime = date("Y-m-d H:i:s", strtotime("+2 minutes"));

            // Generate short-lived token (instead of sending email in URL)
            $token = generateToken(16);
            $tokenExpiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));
            $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Save OTP + token + expiry + IP in DB
            $update = $con->prepare("UPDATE user_account 
                SET otp = :otp, otp_expiry = :otp_expiry, reset_token = :token, 
                    reset_expiry = :token_expiry, reset_purpose = 'reset_password',
                    reset_token_ip = :user_ip
                WHERE user_email = :email");
            $update->execute([
                ':otp' => $otp,
                ':otp_expiry' => $expiryTime,
                ':token' => $token,
                ':token_expiry' => $tokenExpiry,
                ':user_ip' => $userIP,
                ':email' => $email
            ]);

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

            // Send OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'bridge.mcu@gmail.com';
                $mail->Password = 'pmpj kqij isdi jtwl'; // Gmail app password
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('bridge.mcu@gmail.com', 'MCU Bridge');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Reset Password';
                $mail->Body = "We received a request to reset your password.<br><br>
                               Your OTP code is: <b>$otp</b> <br><br>
                               This code will expire in 2 minutes.<br><br>
                               If you did not request a reset, ignore this email.";

                $mail->send();

                echo json_encode([
                    "success" => true,
                    "redirect" => "verify-account?token=" . urlencode($token) . "&newotp=1",
                    "email" => $email,
                    "expiry" => strtotime($expiryTime) * 1000 // ms timestamp   
                ]);
                exit();
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Mailer Error: " . $mail->ErrorInfo
                ]);
                exit();
            }

        } else {
            echo json_encode([
                "success" => false,
                "message" => "No account found with that email."
            ]);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Server error: " . $e->getMessage()
        ]);
        exit();
    }
}
?>
