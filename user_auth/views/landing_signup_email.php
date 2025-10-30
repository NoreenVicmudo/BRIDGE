<?php 
session_start();
include '../../core/j_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="user_auth/css/landing_pages.css">
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
            <i class="bi bi-envelope-check status-icon success"></i>
            <h2 class="text-center fw-semibold mb-4">Email Sent!</h2>
            <div class="text-center">
                <p class="landing-message">We have sent a verification email to your email address. The link will expire in 1 hour.</p>
                <div class="countdown-container">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Redirecting in <span id="countdown">10</span> seconds
                </div>
            </div>
        </div>
    </body>
    <script src="user_auth/js/landing.js"></script>
    <script src="user_auth/js/general_login.js"></script>
</html>
