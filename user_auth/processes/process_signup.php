<?php
session_start();
include '../../core/j_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName        = trim($_POST['lastName']);
    $firstName       = trim($_POST['firstName']);
    $username        = trim($_POST['username']);
    $email           = trim($_POST['email']); // prefilled, must exist
    $college         = trim($_POST['college']);
    $position        = trim($_POST['position']); // maps to user_level
    $program         = isset($_POST['program']) ? trim($_POST['program']) : null;
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Required fields check
    if (!$lastName || !$firstName || !$username || !$email || !$college || !$position || !$password || !$confirmPassword) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    //Character length validation
    if (strlen($firstName) > 50 || strlen($lastName) > 50) {
        echo json_encode(["success" => false, "message" => "First/Last name cannot exceed 50 characters."]);
        exit;
    }
    
    if (strlen($username) > 30) {
        echo json_encode(["success" => false, "message" => "Username cannot exceed 30 characters."]);
        exit;
    }

    // Password validation
    if ($password !== $confirmPassword) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }

    if (strlen($password) < 8) {
        echo json_encode(["success" => false, "message" => "Password must be at least 8 characters."]);
        exit;
    }

    // Check for spaces
    if (preg_match('/\s/', $password)) {
        echo json_encode(["success" => false, "message" => "Password must not contain spaces."]);
        exit;
    }

    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least one uppercase letter."]);
        exit;
    }

    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least one lowercase letter."]);
        exit;
    }

    // Check for number
    if (!preg_match('/\d/', $password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least one number."]);
        exit;
    }

    // Check for special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least one special character."]);
        exit;
    }

    // Password hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Ensure the email exists in DB
        $sql = "SELECT user_id, is_active FROM user_account WHERE user_email = :email";
        $stmt = $con->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Invalid email or expired token."]);
            exit;
        }

        if ($user['is_active'] == 1) {
            echo json_encode(["success" => false, "message" => "Account already active."]);
            exit;
        }

        // Update the user row with signup details
        $update = "UPDATE user_account 
        SET user_lastname   = :lastname,
            user_firstname  = :firstname,
            user_username   = :username,
            user_password   = :password,
            user_college    = :college,
            user_program    = :program,
            user_level      = :level,
            is_active       = 0,
            reset_token     = NULL,
            reset_expiry    = NULL,
            reset_purpose   = NULL,
            signup_completed_at = NOW()
        WHERE user_email = :email";


        $stmt = $con->prepare($update);
        $stmt->execute([
            ':lastname'  => $lastName,
            ':firstname' => $firstName,
            ':username'  => $username,
            ':password'  => $hashedPassword,
            ':college'   => $college,
            ':program'   => $program,
            ':level'     => $position,
            ':email'     => $email
        ]);

        echo json_encode(["success" => true, "message" => "Signup submitted. Awaiting admin approval."]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        exit;
    }
}
?>
