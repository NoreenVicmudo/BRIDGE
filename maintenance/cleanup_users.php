<?php
require_once("../core/j_conn.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../core/phpmailer/src/Exception.php';
require '../core/phpmailer/src/PHPMailer.php';
require '../core/phpmailer/src/SMTP.php';

function sendUserEmail($email, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
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
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Get expired accounts
    $stmt = $con->prepare("
        SELECT user_id, user_email 
        FROM user_account
        WHERE is_active = 0
          AND signup_completed_at IS NOT NULL
          AND signup_completed_at < (NOW() - INTERVAL 1 MONTH)
    ");
    $stmt->execute();
    $expiredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expiredUsers as $user) {
        // --- Future-proof logging (uncomment once table exists) ---
        /*
        $log = $con->prepare("
            INSERT INTO transaction_logs (user_id, user_email, deleted_at, reason)
            VALUES (?, ?, NOW(), ?)
        ");
        $log->execute([
            $user['user_id'],
            $user['user_email'],
            'Signup request expired (no approval within 1 month)'
        ]);
        */

        sendUserEmail(
            $user['user_email'],
            "MCU Bridge Account Request Expired",
            "Your signup request has expired after 1 month without approval. Please sign up again if needed."
        );

        // Delete expired user
        $del = $con->prepare("DELETE FROM user_account WHERE user_id = ?");
        $del->execute([$user['user_id']]);
    }

    echo "Cleanup complete. Processed " . count($expiredUsers) . " expired user(s).";
} catch (Exception $e) {
    echo "Cleanup failed: " . $e->getMessage();
}

//future proofing -- add date for when will be deleted or add date when it got deleted