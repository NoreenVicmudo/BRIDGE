<?php
require_once __DIR__ . "/../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

// Ensure session is completely cleared
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Clear any cookies that might be set
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Start a completely fresh session to ensure clean state
session_start();

// Don't include auth.php here since we're already logged out
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="public/css/landing.css">
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>   
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
        <title>Session Timeout</title>
    </head>
    <body>
        <div class="container d-flex justify-content-center align-items-center min-vh-100">
            <div class="row shadow-lg p-3 mb-5 bg-white rounded timeout-container" style="max-width: 600px; width: 100%;">
                <div class="col-12 p-4 text-center">
                    <!-- Logo -->
                    <div class="mb-4">
                        <img src="Pictures/purple_logo.png" alt="MCU Logo" class="img-fluid" style="max-width: 200px;">
                    </div>
                    
                    <!-- Timeout Message -->
                    <div class="mb-4">
                        <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
                        <h2 class="fw-semibold mb-3 mt-3">Session Expired</h2>
                        <p class="text-muted mb-4">Your session has timed out due to inactivity. For security reasons, you need to log in again.</p>
                    </div>
                    
                    <!-- Action Button -->
                    <div class="d-flex justify-content-center">
                        <a href="login" class="btn btn-primary back-page" id="loginButton">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                        </a>
                    </div>
                    
                    <!-- Help Text -->
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Sessions expire after 5 minutes of inactivity for security purposes.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <script src="public/js/general_public.js"></script>
    </body>
</html>
