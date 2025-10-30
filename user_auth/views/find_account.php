<?php 
session_start();
include '../../core/j_conn.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="user_auth/css/find_account.css">
        <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
        <title>BRIDGE</title>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body class="d-flex justify-content-center align-items-center min-vh-100">
        <a class="go-back back-page d-flex align-items-center" href="login">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
        <div class="form-container">   
            <p class="form-title">Forget Password</p>

            <form method="POST" action="user_auth/processes/process_find_account.php" class="d-flex flex-column">
                <div class="input-container">
                    <input id="email" placeholder=" " type="email" name="email" title="Please enter a valid email address." required>
                    <label for="email">Find your email</label>             
                    <button class="btn-search" type="submit" id="searchBtn">
                        <span class="btn-text">Search</span>
                        <div class="loader" id="loader"></div>
                    </button>
                </div>
                <div class="message" id="messageBox" style="margin-top:10px;"></div> <!-- Email not in db-->
            </form>
        </div>
        <script src="user_auth/js/general_login.js"></script>
        <script src="user_auth/js/find_account.js"></script>
    </body>
</html>
