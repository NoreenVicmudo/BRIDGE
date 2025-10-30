<?php
session_start();
include '../../core/j_conn.php';

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../core/phpmailer/src/Exception.php';
require '../../core/phpmailer/src/PHPMailer.php';
require '../../core/phpmailer/src/SMTP.php';

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    try {
        // Check if email already belongs to a fully registered/active user
        $stmt = $con->prepare("SELECT user_id 
                            FROM user_account 
                            WHERE user_email = :email 
                                AND (is_active = 1 OR signup_completed_at IS NOT NULL)");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Email already registered
            echo json_encode([
                "success" => false,
                "message" => "This email is already taken."
            ]);
            exit();
        } else {
            // Check for existing signup attempt
            $check = $con->prepare("SELECT reset_expiry 
                                    FROM user_account 
                                    WHERE user_email = :email 
                                    AND reset_purpose = 'signup'
                                    AND signup_completed_at IS NULL
                                    AND is_active = 0
                                    ORDER BY reset_expiry DESC LIMIT 1");
            $check->execute([':email' => $email]);
            $pending = $check->fetch(PDO::FETCH_ASSOC);

            if ($pending && strtotime($pending['reset_expiry']) > time()) {
                // 🔒 User still has a valid link → block new request
                echo json_encode([
                    "success" => false,
                    "message" => "You already have a signup link. Please wait until it expires."
                ]);
                exit();
            }

            // If we reach here, either no record or expired → cleanup old
            $delete = $con->prepare("DELETE FROM user_account 
                                    WHERE user_email = :email 
                                    AND reset_purpose = 'signup'
                                    AND signup_completed_at IS NULL
                                    AND is_active = 0");
            $delete->execute([':email' => $email]);

            //Not used -> create tokenW
            $token = generateToken(16);
            $tokenExpiry = date("Y-m-d H:i:s", strtotime("+1 hour")); //1 hr validity

            // Insert pending signup
            $insert = $con->prepare("INSERT INTO user_account
                (user_email, reset_token, reset_expiry, reset_purpose, is_active) 
                VALUES (:email, :token, :expiry, 'signup', 0)");

            $insert->execute([
                ':email' => $email,
                ':token' => $token,
                ':expiry' => $tokenExpiry
            ]);

            // Send verification email
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

                //TODO: Change to production URL
                $verifyLink = "http://localhost/bridge/signup?token=" . urlencode($token);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email';
                $mail->Body = "Thanks for signing up!<br><br>
                               Please click the link below to verify your email and complete your registration:<br><br>
                               <a href='$verifyLink'>$verifyLink</a><br><br>
                               This link will expire in 1 hour.";

                $mail->send();

                echo json_encode([
                    "success" => true,
                    "redirect" => "verify-email-sent"
                ]);
                exit();
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Mailer Error: " . $mail->ErrorInfo
                ]);
                exit();
            }
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
