<?php
session_start();
include '../../core/j_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Get user regardless of active status
    $sql = "SELECT * FROM user_account WHERE user_email = :email LIMIT 1";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');

    if ($row && $row['is_active'] == 1 && password_verify($password, $row['user_password'])) {
        // ✅ Regenerate session ID after successful login
        session_regenerate_id(true);

        // Successful login
        $_SESSION['id'] = $row['user_id'];
         //Convert the image blob to a Base64 string before storing in session
        if ($row['user_profile_pic']) {
            $_SESSION['profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($row['user_profile_pic']);
        } else {
            $_SESSION['profile_pic'] = null;
        }
        $_SESSION['email'] = $row['user_email'];
        $_SESSION['level'] = $row['user_level'];
        $_SESSION['college'] = $row['user_college'];
        $_SESSION['program'] = $row['user_program'];
        $_SESSION['username'] = $row['user_username'];
        $_SESSION['firstname'] = $row['user_firstname']; // Change key to 'firstname'
        $_SESSION['lastname'] = $row['user_lastname'];   // Change key to 'lastname'

        // Clear flag, but don't fail login if DB update has an issue
        try {
            $stmtClear = $con->prepare("UPDATE user_account SET force_relogin = 0 WHERE user_id = :id");
            $stmtClear->execute([':id' => $row['user_id']]);
        } catch (PDOException $e) {
            // log if you have logger, but don't block login
            error_log("Failed to clear force_relogin for user {$row['user_id']}: " . $e->getMessage());
        }

        echo json_encode(["success" => true]);
    } elseif ($row && $row['is_active'] == 0) {
        // Account not active -> only show landing if still pending
        echo json_encode(["success" => false, "redirect" => "account-pending?reason=pending"]);
    } else {
        // Wrong credentials
        echo json_encode(["success" => false, "message" => "Wrong login credentials. Please try again."]);
    }
}
