<?php
session_start();
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../core/phpmailer/src/Exception.php';
require '../../../core/phpmailer/src/PHPMailer.php';
require '../../../core/phpmailer/src/SMTP.php';

function generateOTP($length = 6) {
    $otp = random_int(0, pow(10, $length) - 1);
    return str_pad($otp, $length, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $newEmail = trim($_POST['email'] ?? '');

    if ($action !== "request_otp" || empty($newEmail)) {
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit;
    }
/*
    // Only Gmail allowed (same as before)
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $newEmail)) {
        echo json_encode(["success" => false, "message" => "Only Gmail addresses are allowed."]);
        exit;
    }*/

    try {
        // Generate OTP and expiry
        $otp = generateOTP();
        $expiryTime = date("Y-m-d H:i:s", strtotime("+2 minutes"));

        // Save OTP + expiry in DB (for current user)
        $stmt = $con->prepare("UPDATE user_account 
            SET otp = :otp, otp_expiry = :otp_expiry, reset_purpose = 'change_email'
            WHERE user_id = :uid");
        $stmt->execute([
            ':otp' => $otp,
            ':otp_expiry' => $expiryTime,
            ':uid' => $_SESSION['id']
        ]);

        // Send OTP to new email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bridge.mcu@gmail.com';
        $mail->Password = 'pmpj kqij isdi jtwl'; // app password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('bridge.mcu@gmail.com', 'MCU Bridge');
        $mail->addAddress($newEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Change Email Verification';
        $mail->Body = "You requested to change your account email.<br><br>
                       Your OTP code is: <b>$otp</b><br><br>
                       This code will expire in 2 minutes.<br><br>
                       If you did not request this, ignore this email.";

        $mail->send();

        echo json_encode([
            "success" => true,
            "email" => $newEmail,
            "expiry" => strtotime($expiryTime) * 1000 // ms timestamp
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Mailer Error: " . $e->getMessage()]);
    }
}
?>