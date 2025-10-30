<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../core/phpmailer/src/Exception.php';
require '../../../core/phpmailer/src/PHPMailer.php';
require '../../../core/phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';

    if (!$userId || !$action) {
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit;
    }

    try {
        // Fetch user info
        $stmt = $con->prepare("SELECT user_email, user_firstname, user_lastname FROM user_account WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            exit;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bridge.mcu@gmail.com';
        $mail->Password = 'pmpj kqij isdi jtwl'; // app password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('bridge.mcu@gmail.com', 'MCU Bridge');
        $mail->addAddress($user['user_email']);
        $mail->isHTML(true);

        if ($action === 'accept') {
            // Approve account
            $sql = "UPDATE user_account 
                    SET is_active = 1, account_status = 'approved', decision_date = NOW() 
                    WHERE user_id = ?";
            $stmt = $con->prepare($sql);
            $stmt->execute([$userId]);

            // Send approval email
            $mail->Subject = "Signup Approved";
            $mail->Body = "Hi {$user['user_firstname']},<br><br>Your signup request has been approved! You can now log in to your account.<br><br>Thank you.";
            $mail->send();

            echo json_encode(["success" => true, "message" => "User approved."]);

        } elseif ($action === 'reject') {
            // Reject account → update status and set decision_date
            $sql = "UPDATE user_account 
                    SET account_status = 'rejected', decision_date = NOW() 
                    WHERE user_id = ?";
            $stmt = $con->prepare($sql);
            $stmt->execute([$userId]);

            // Send rejection email
            $mail->Subject = "Signup Rejected";
            $mail->Body = "Hi {$user['user_firstname']},<br><br>Unfortunately, your signup request was rejected by the administrator.<br><br>
                            Please contact support if you believe this was a mistake.";
            $mail->send();

            // Delete user after sending email
            $stmt = $con->prepare("DELETE FROM user_account WHERE user_id = ?");
            $stmt->execute([$userId]);

            echo json_encode(["success" => true, "message" => "User rejected."]);

        } else {
            echo json_encode(["success" => false, "message" => "Unknown action"]);
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
