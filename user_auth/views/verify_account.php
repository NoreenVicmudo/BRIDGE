<?php 
session_start();
include '../../core/j_conn.php';

$isNewOtp = isset($_GET['newotp']) && $_GET['newotp'] == "1";

// Require token in query string
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: token-expired');
    exit();
}

$token = $_GET['token'];
$userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Look up user from reset_token table/column
$stmt = $con->prepare("SELECT user_firstname, user_lastname, user_email, reset_token_ip 
    FROM user_account 
    WHERE reset_token = :token 
        AND reset_expiry > NOW()
        AND reset_purpose = 'reset_password'");
$stmt->bindParam(':token', $token);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: token-expired');
    exit();
}

// ✅ IP VALIDATION (optional - logs mismatch but allows)
if (!empty($user['reset_token_ip']) && $user['reset_token_ip'] !== $userIP) {
    // Log suspicious activity (you can add logging here later)
    error_log("Password reset IP mismatch for email: " . $user['user_email'] . 
              " Expected: " . $user['reset_token_ip'] . " Got: " . $userIP);
}

$fname = $user['user_firstname'];
$lname = $user['user_lastname'];
$email = $user['user_email'];
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="user_auth/css/verify_account.css">
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
        <title>BRIDGE</title>
    </head>
    <body>
        <!-- Go Back Button -->
        <a class="go-back back-page" href="forget-password"><i class="bi bi-arrow-left"></i> Back</a>

        <div class="form-container">
            <p class="form-title">Is this you?</p>

            <form method="POST" action="/bridge/user_auth/processes/process_verify_account.php" id="otpForm">
                <div class="user-info">
                    <h2><?php echo htmlspecialchars($fname . ' ' . $lname); ?></h2><br>
                    <h6><?php echo htmlspecialchars($email); ?></h6>
                   <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                   <input type="hidden" name="code" id="otpCode" value="">  <!-- Add this line -->
                </div>
                <!-- OTP Input -->
                <div class="otp-container">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 0)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 0)">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 1)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 1)">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 2)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 2)">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 3)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 3)">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 4)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 4)">
                    <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 5)" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 5)">
                </div>

                <p id="otpMessage" style="display: none; color: green; font-weight: bold;"></p>
                <p id="timerText"><span id="timer"></span></p>

                <!-- Error Message -->
                <div class="message" style="display:none; color:red;">Invalid OTP Code</div>

                <!-- Verify Loader -->
                <div id="verifyLoader" style="display:none; margin:10px auto;"></div>

                <!-- Resend Button (hidden by default) -->
                <button type="button" class="btn" id="resendBtn" onclick="resendCode()" style="display:none;">
                    <span class="btn-text">Resend Code</span>
                    <div class="loader" id="loader"></div>
                </button>
            </form>



            <!-- Alternative Login Option -->
            <a href="login" class="login back-page">Login with password</a>
        </div>
        <script src="user_auth/js/general_login.js"></script>
        <script src="user_auth/js/verify_account.js"></script>
    </body>
</html>
