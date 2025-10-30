<?php 
session_start();
include '../../core/j_conn.php';

// Get the reset token (from query or POST)
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    header('Location: token-expired');
    exit();
}

// Validate token before showing form
$stmt = $con->prepare("SELECT user_email FROM user_account WHERE reset_token = :token AND reset_expiry > NOW()");
$stmt->bindParam(':token', $token);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: token-expired');
    exit();
}

$email = $user['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="user_auth/css/change_password.css">
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
        <title>BRIDGE</title>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body>
        <a class="go-back back-page" href="login"><i class="bi bi-arrow-left"></i> Back</a>
        <div class="form-container shadow-lg rounded-4 bg-white">
            <h2 class="text-center fw-semibold mb-4">Change Password</h2>
            <div class="user-info">
                <h5>New password in this email:</h5><br>
                <h6><?php echo htmlspecialchars($email); ?></h6>
            </div>

            <form method="POST" action="user_auth/processes/process_change_password.php" id="password_change">
                 <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-group">
                    <input type="password" id="new-password" name="newPassword" placeholder=" " required>
                    <label for="new-password">New Password</label>
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('new-password', this)"></i>
                </div>
                <br>
                <div class="input-group">
                    <input type="password" id="confirm-password" name="confirmPassword" placeholder=" " required>
                    <label for="confirm-password">Confirm Password</label>
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirm-password', this)"></i>
                </div>

                <div class="message" style="display: none;"></div>
                <br>
                <button class="btn" type="submit" id="change-passwordBtn">
                    <span class="btn-text">Submit</span>
                    <div class="loader" id="loader"></div>
                </button>
            </form>

            
        </div>
        <script src="user_auth/js/general_login.js"></script>
        <script src="user_auth/js/change_password.js"></script>
    </body>
</html>