<?php 
session_start();
include '../../core/j_conn.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $con->prepare("SELECT user_email, reset_expiry
                           FROM user_account
                           WHERE reset_token = :token 
                             AND reset_purpose = 'signup'
                             AND is_active = 0");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (strtotime($row['reset_expiry']) > time()) {
            // ✅ Token is still valid
            $email = $row['user_email'];
        } else {
            // ❌ Token expired → delete stale row
            $delete = $con->prepare("DELETE FROM user_account
                                     WHERE reset_token = :token 
                                       AND reset_purpose = 'signup'
                                       AND signup_completed_at IS NULL
                                       AND is_active = 0");
            $delete->execute([':token' => $token]);

            // ❌ Token expired
            header("Location: signup-error?reason=expired");
            exit;
        }
    } else {
        // ❌ Token not found
        header("Location: signup-error?reason=invalid");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
        <link rel="stylesheet" href="user_auth/css/signup.css" />
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css"/>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
        <title>BRIDGE</title>
    </head>
    <body>
        <div class="form-wrapper">
            <a class="go-back back-page d-inline-flex align-items-center" href="login">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <div class="form-container p-4 rounded shadow text-center">
                <h2>Sign Up</h2>
                <form method="POST" action="user_auth/processes/process_signup.php" id="signup">
                    <div class="name-row">
                        <div class="input-group">
                            <input type="text" id="firstname" name="firstName" placeholder=" " required required maxlength="50"/>
                            <label for="firstname">First Name</label>
                            <span class="char-count" id="firstname-count">0/50</span>
                        </div>
                        <div class="input-group">
                            <input type="text" id="lastname" name="lastName" placeholder=" " required required maxlength="50"/>
                            <label for="lastname">Last Name</label>
                            <span class="char-count" id="lastname-count">0/50</span>
                        </div>                        
                    </div>
                    <div class="input-group">
                        <input type="text" id="username" name="username" placeholder=" " required required maxlength="30"/>
                        <label for="username">Username</label>
                        <span class="char-count" id="username-count">0/30</span>
                    </div>
                    <div class="input-group">
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"placeholder=" " required disabled/>
                        <label for="email">Email</label>
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <!-- COLLEGE COMBOBOX -->
                    <div class="input-group position-relative">
                        <select id="college" name="college"  required>
                            <option value="" disabled selected></option>
                        </select>
                        <label for="college">Select College</label>
                        <i class="bi bi-caret-down caret"></i>
                    </div>
                    <!-- POSITION COMBOBOX -->
                    <div class="input-group position-relative">
                        <select id="position" name="position" required>
                            <option value="" disabled selected></option>
                        </select>
                        <label for="position">Select Position</label>
                        <i class="bi bi-caret-down caret"></i>
                    </div>
                    <!-- Program Selection (Hidden until conditions are met) -->
                    <div class="input-group position-relative" id="programGroup" style="display: none;">
                        <select id="program" name="program" required>
                            <option value="" disabled selected></option>
                        </select>
                        <label for="program">Select Program</label>
                        <i class="bi bi-caret-down caret"></i>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " required/>
                        <label for="password">Password</label>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                    
                    <!-- Password Requirements Checklist -->
                    <div class="password-requirements" id="password-requirements">
                        <div class="requirement-item" id="req-length">
                            <i class="bi bi-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement-item" id="req-spaces">
                            <i class="bi bi-circle"></i>
                            <span>No spaces</span>
                        </div>
                        <div class="requirement-item" id="req-uppercase">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 uppercase letter</span>
                        </div>
                        <div class="requirement-item" id="req-lowercase">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 lowercase letter</span>
                        </div>
                        <div class="requirement-item" id="req-number">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 number</span>
                        </div>
                        <div class="requirement-item" id="req-special">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 special character</span>
                        </div>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required/>
                        <label for="confirm_password">Confirm Password</label>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <div class="message" id="error-message">Passwords do not match.<br></div>
                    <button class="btn" type="submit" id="signupBtn">
                        <span class="btn-text">Submit</span>
                        <div class="loader" id="loader"></div>
                    </button>
                </form>
            </div>
        </div>
        

        <!-- TERMS OF SERVICE MODAL -->
        <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content shadow-lg rounded-4">
                    <!-- Centered Header -->
                    <div class="modal-header border-bottom-0 pb-0 justify-content-center">
                        <h4 class="modal-title display-6 fw-bold text-center w-100" id="termsModalLabel">Terms of Service</h4>
                    </div>
                    <!-- Scrollable Body -->
                    <div class="modal-body overflow-auto px-4 pt-2" style="max-height: 65vh;">
                        <div class="mb-4 text-muted">
                            <p class="mb-1">By using this service, you agree to our Terms of Service and Privacy Policy.</p>
                            <p>Please read them carefully before proceeding.</p>
                        </div>
                        <!-- TOS Sections -->
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">User Responsibility</h6>
                            <p>Users must provide accurate and truthful information during registration and while using the system.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">Account Security</h6>
                            <p>Users are responsible for keeping their login credentials secure. Report unauthorized access immediately.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">Data Usage</h6>
                            <p>Your data will only be used for academic support, tracking, and internal analytics. It won't be shared publicly.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">Acceptable Use</h6>
                            <p>No spamming, hacking, or misuse of system features. Violations may lead to suspension or banning.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">System Availability</h6>
                            <p>We aim for 24/7 access but can't guarantee uninterrupted service. Scheduled maintenance will be announced.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">Privacy</h6>
                            <p>Your data is protected under our privacy policy. Only authorized personnel (admins, deans, IT) can access it.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark">Modifications</h6>
                            <p>We may update features or policies. Continued use means you accept those changes.</p>
                        </div>
                        <div class="mb-4">
                            <h6 class="fw-semibold text-dark">Consent</h6>
                            <p>By signing up, you agree to the collection and use of your data as described.</p>
                        </div>
                        <!-- Checkbox -->
                        <div class="d-flex justify-content-center mt-4">
                            <div class="form-check d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" id="agreeCheckbox">
                                <label class="form-check-label" for="agreeCheckbox">
                                    I agree to the Terms of Service and Privacy Policy
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- Footer Buttons -->
                    <div class="modal-footer px-4 d-flex justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn next-page" id="declineButton">Decline</button>
                            <div class="loader" id="loader" style="display:none;"></div>
                        </div>
                        <div>
                            <button type="button" class="btn fw-semibold px-4" id="acceptButton" data-bs-dismiss="modal" style="display: none;">
                                Accept
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="user_auth/js/general_login.js"></script>
        <script src="user_auth/js/signup.js"></script>
    </body>
</html>
