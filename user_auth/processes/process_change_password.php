<?php
session_start();
require_once '../../core/j_conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validate inputs
    if (empty($newPassword) || empty($confirmPassword)) {
        die("All fields are required.");
    }

    if ($newPassword !== $confirmPassword) {
        die("Passwords do not match.");
    }

    if (strlen($newPassword) < 8) {
        die("Password must be at least 8 characters.");
    }

    // Check for spaces
    if (preg_match('/\s/', $newPassword)) {
        die("Password must not contain spaces.");
    }

    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $newPassword)) {
        die("Password must contain at least one uppercase letter.");
    }

    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $newPassword)) {
        die("Password must contain at least one lowercase letter.");
    }

    // Check for number
    if (!preg_match('/\d/', $newPassword)) {
        die("Password must contain at least one number.");
    }

    // Check for special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $newPassword)) {
        die("Password must contain at least one special character.");
    }

    try {
        // Find user by token
        $stmt = $con->prepare("SELECT user_email FROM user_account WHERE reset_token = :token AND reset_expiry > NOW()");
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: token-expired');
            exit();
        }

        $email = $user['user_email'];

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password + clear token + reset attempt counter
        $sql = "UPDATE user_account 
                SET user_password = :password, 
                    reset_token = NULL, 
                    reset_expiry = NULL, 
                    otp = NULL, 
                    otp_expiry = NULL,
                    password_reset_attempts_count = 0,
                    password_reset_attempts_reset_at = NULL,
                    last_password_change = NOW()  -- ✅ saves the exact date/time
                WHERE user_email = :email";

        $stmt = $con->prepare($sql);
        $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "failed";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: change-password");
    exit;
}
