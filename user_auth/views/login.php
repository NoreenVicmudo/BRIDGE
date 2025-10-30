<?php session_start();
    include '../../core/j_conn.php';

    if (!empty($_SESSION['id'])) {
    header("Location: /bridge/");
    exit();
}?>
    
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
                rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
                integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <link rel="stylesheet" href="user_auth/css/login.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
            <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
            <title>BRIDGE</title>
        </head>
        <body>
            <div class="container d-flex justify-content-center align-items-center min-vh-100 custom-container">
                <div class="row shadow-lg p-3 mb-5 bg-white rounded" style="max-width: 900px; width: 100%;">
                    <div class="col-md-6 d-flex flex-column justify-content-center align-items-center p-3">
                        <div class="logo-title-wrapper w-100 d-flex flex-column align-items-start align-items-md-center">
                            <img src="Pictures/purple_logo.png" alt="MCU Logo" class="logo img-fluid mb-2 mx-auto rounded-3" style="max-width: 320px; min-width: 120px; width: 100%;">
                        </div>
                    </div>

                    <div class="col-md-6 p-4">
                        <form method="POST" class="form" action="user_auth/processes/process_login.php" id="login">                           

                            <div class="input-group custom-input-group">
                                <input type="email" name="email" id="email" placeholder=" " required />
                                <label for="email">Email address</label>
                            </div>

                            <div class="input-group custom-input-group">
                                <input type="password" id="password" name="password" placeholder=" " required>
                                <label for="password">Password</label>
                                <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
                            </div>

                            <div class="message"></div>
                    
                            <button class="submit-button next-page" type="submit" id="loginBtn">
                                <span class="btn-text">Log In</span>
                                <div class="loader" id="loader"></div>
                            </button>

                            <p class="forgotpassword-link">
                                <a href="forget-password" class="next-page">Forgot Password?</a>
                            </p>
                            <p class="forgotpassword-link">
                                <a href="verify-email" class="next-page">Create Account</a>
                            </p>

                            <!-- Loader div -->
                            <div class="loader" id="loader"></div>
                        </form>
                    </div>
                </div>
            </div>
            <script src="user_auth/js/login.js"></script>
        </body>        
    </html>
