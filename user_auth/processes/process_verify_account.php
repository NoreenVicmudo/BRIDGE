<?php 
session_start();
include '../../core/j_conn.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $code  = $_POST['code'] ?? '';

    try {
        $stmt = $con->prepare("SELECT * FROM user_account WHERE reset_token = :token AND reset_purpose = 'reset_password' AND otp = :code");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $otpExpiry = $result['otp_expiry']; 
            $currentTime = date("Y-m-d H:i:s");

            if ($currentTime > $otpExpiry) {
                echo json_encode(["success" => false, "message" => "OTP expired"]);
                exit;
            }

            // ✅ TOKEN ROTATION - Generate new token after OTP verification
            $newToken = bin2hex(random_bytes(16));
            $newTokenExpiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

            // Update with new token and clear OTP
            $stmt = $con->prepare("UPDATE user_account 
                SET reset_token = :new_token, 
                    reset_expiry = :new_expiry,
                    otp = NULL,
                    otp_expiry = NULL
                WHERE user_email = :email");
            $stmt->execute([
                ':new_token' => $newToken,
                ':new_expiry' => $newTokenExpiry,
                ':email' => $result['user_email']
            ]);

            // ✅ OTP valid
            echo json_encode([
                "success" => true,
                "redirect" => "change-password?token=" . urlencode($newToken)
            ]);
            exit;

        } else {
            echo json_encode(["success" => false, "message" => "Invalid OTP"]);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Server error"]);
        exit;
    }
}
