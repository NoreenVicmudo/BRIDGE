<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

$user_id = $_SESSION['id'] ?? null;
if (!$user_id) {
    die("Not logged in");
}

// 🌟 FIX: Fetch user info from the database every time 🌟
$stmt = $con->prepare("SELECT user_username, user_profile_pic, user_email
                       FROM user_account WHERE user_id = :id LIMIT 1");
$stmt->execute([":id" => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found in DB");
}

// Check if a profile picture exists in the session
// If not, use the data we just fetched
if (!isset($_SESSION['profile_pic'])) {
    if ($user['user_profile_pic']) {
        $_SESSION['profile_pic'] = 'data:image/jpeg;base64,' . base64_encode($user['user_profile_pic']);
    } else {
        $_SESSION['profile_pic'] = '../../../Pictures/default.jpg';
    }
}

$username = $_SESSION['username'] ?? 'User';
$profilePic = $_SESSION['profile_pic'] ?? '../../../Pictures/default.jpg';
$user_email = htmlspecialchars($user['user_email']); // Override when changed email
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
        <link rel="stylesheet" href="modules/settings/css/settings.css" />
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css"/>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <title>BRIDGE</title>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="form-wrapper">
            <a class="go-back back-page d-flex align-items-center" href="home">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <div class="settings-wrapper p-4 rounded shadow text-center">
                <!-- Sidebar -->
                <div class="sidebar">
                    <a href="#" class="active" onclick="showTab('profile', event)">Profile</a>
                    <a href="#" onclick="showTab('account', event)">Account</a>
                </div>

                <!-- Content -->
                <div class="form-container">
                    <!-- Profile Tab -->
                    <div id="profile-tab" class="tab-content">
                        <h2>Profile Settings</h2>
                        <form id="profile-form" enctype="multipart/form-data">
                          <div class="profile-pic-wrapper">
                            <div class="pic-loader" id="pic-loader"></div>
                            <img src="<?= $profilePic ?>" id="profile-pic" class="profile-pic">
                            

                            <i class="bi bi-camera upload-icon" onclick="document.getElementById('file-upload').click()"></i>
                            <input type="file" id="file-upload" name="profile_pic" accept="image/*" style="display:none"><!--onchange="previewImage(event)"-->
                          </div>

                          <div class="input-group">
                              <input type="text" id="username" name="username" placeholder=" " oninput="toggleActionButtons(); updateCharCount()" maxlength="30" value="<?= htmlspecialchars($username) ?>">
                              <label for="username">Username</label>
                              <div class="char-count" id="char-count">0/30</div>
                          </div>


                          <div class="action-buttons" id="profile-actions">
                              <button class="btn-cancel btn-cancel-profile-change" onclick="revertChanges()">
                                <span class="btn-text">Cancel</span>
                              </button>
                              <button class="btn-save" onclick="saveChanges()">
                                <span class="btn-text">Save</span>
                                <div class="loader"></div>
                              </button>                  
                          </div>
                        </form>
                    </div>

                    <!-- Account Tab -->
                    <div id="account-tab" class="tab-content" style="display:none">
                        <h2>Account Settings</h2>
                        <button class="btn-option" onclick="openModal('email-modal')">
                          <span>Email: <b id="current-email"><?= htmlspecialchars($user_email) ?></b></span>
                          <i class="bi bi-caret-right"></i>
                        </button>
                        <button class="btn-option" onclick="openModal('password-modal')">
                          <span>Change Password</span>
                          <i class="bi bi-caret-right"></i>
                        </button>               
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Modal -->
        <div id="email-modal" class="modal">
          <div class="modal-content" id="email-step1">
            <h2>Change Email</h2>
              <div class="form-group">              
                <input type="email" id="new-email" placeholder="Enter new email">        
              </div>
              <div class="message" style="text-align:center"></div>
              <div class="modal-buttons">
                <button class="btn btn-cancel-modal btn-cancel-email-step1" onclick="closeModal('email-modal')">Cancel</button>
                <button class="btn btn-confirm" onclick="startEmailOtp()">
                  <span class="btn-text">Confirm</span>
                  <div class="loader"></div>
                </button>
              </div>
          </div>

          <div class="modal-content" id="email-step2" style="display:none; text-align:center;">
            <h2>Email Verification</h2>
            <p>Email: <br><b id="otp-email-display"></b></p>

            <!-- OTP Input -->
            <div class="otp-container">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 0, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 0, 'email')"  onpaste="handlePaste(event,'email')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 1, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 1, 'email')"  onpaste="handlePaste(event,'email')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 2, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 2, 'email')"  onpaste="handlePaste(event,'email')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 3, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 3, 'email')"  onpaste="handlePaste(event,'email')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 4, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 4, 'email')"  onpaste="handlePaste(event,'email')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 5, 'email')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 5, 'email')"  onpaste="handlePaste(event,'email')">
            </div>

            <p id="otpMessage" style="display: none; color: green; font-weight: bold;"></p>
            <p id="timerText"><span id="timer"></span></p>

            <!-- Error Message -->
            <div class="message" id="emailError" style="display:none; color:red;">Invalid OTP Code</div>

            <!-- Verify Loader -->
            <div id="verifyLoaderEmail" style="display:none; margin:10px auto;"></div>

            <!-- Resend / Cancel -->
            <div class="modal-buttons">
              <button type="button" class="btn btn-cancel-modal btn-cancel-resend-email" onclick="closeModal('email-modal')">Cancel</button>
              <button type="button" class="btn btn-resend" id="resendBtnEmail" onclick="resendCode('email')" style="display:none;">
                <span class="btn-text">Resend Code</span>
                <div class="loader" id="loaderEmail"></div>
              </button>
            </div>
          </div>
        </div>

        <!-- Password Modal -->
        <div id="password-modal" class="modal">
          <div class="modal-content" id="pass-step1" style="display:block; text-align:center;">
            <h2>Password Verification</h2>

            <!-- Loading State -->
            <div id="otpLoadingState" class="otp-loading-state">
              <div class="circular-loader"></div>
              <p class="loading-message">Sending OTP to your email...</p>
            </div>

            <!-- OTP Input -->
            <div class="otp-container" style="display: none;">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 0, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 0, 'pass')" onpaste="handlePaste(event,'pass')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 1, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 1, 'pass')" onpaste="handlePaste(event,'pass')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 2, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 2, 'pass')" onpaste="handlePaste(event,'pass')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 3, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 3, 'pass')" onpaste="handlePaste(event,'pass')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 4, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 4, 'pass')" onpaste="handlePaste(event,'pass')">
              <input type="text" maxlength="1" class="otp-input" oninput="moveToNext(this, 5, 'pass')" onkeypress="validateNumber(event)" onkeydown="handleBackspace(event, 5, 'pass')" onpaste="handlePaste(event,'pass')">
            </div>

            <p id="otpPassMessage" style="display: none; color: green; font-weight: bold;"></p> 
            <p id="timerPassText"><span id="timerPass"></span></p>

            <!-- Error Message -->
            <div class="message" id="passError" style="display:none; color:red;">Invalid OTP Code</div>

            <!-- Verify Loader -->
            <div id="verifyLoaderPass" style="display:none; margin:10px auto;"></div>

            <div class="modal-buttons">
              <button type="button" class="btn btn-cancel-modal btn-cancel-resend-pass" onclick="closeModal('password-modal')">Cancel</button>
              <button type="button" class="btn btn-resend" id="resendBtnPass" onclick="resendCode('pass')" style="display:none;">
                <span class="btn-text">Resend Code</span>
                <div class="loader" id="loaderPass"></div>
              </button>
            </div>
          </div>

          <div class="modal-content" id="pass-step2" style="display:none;">
            <h2>Change Password</h2>
            <div class="floating-input-group">
              <input type="password" id="old-password" placeholder=" " required>
              <label for="old-password">Current Password</label>
              <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('old-password', this)"></i>
            </div>
            <br>
            <div class="floating-input-group">
              <input type="password" id="new-password" placeholder=" " required>
              <label for="new-password">New Password</label>
              <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('new-password', this)"></i>
            </div>
            <br>
            <div class="floating-input-group">
              <input type="password" id="confirm-password" placeholder=" " required>
              <label for="confirm-password">Confirm Password</label>
              <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirm-password', this)"></i>
            </div>

            <div class="message" id="changePassMsg" style="display: none;">Passwords do not match.</div>
            <br>
            <div class="modal-buttons">
              <button type="button" class="btn btn-cancel-modal btn-cancel-save-pass" onclick="closeModal('password-modal')">Cancel</button>
              <button type="button" class="btn btn-passsave" onclick="savePassword()">
                <span class="btn-text">Submit</span>
                <div class="loader"></div>
              </button>
            </div>
          </div>
        </div>

        <script src="modules/settings/js/settings.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/session_warning.js"></script>
    </body>
</html>
