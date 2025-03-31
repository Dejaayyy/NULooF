<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
include('../data/db.php');

$errorMessage = '';

// LOGIN HANDLER
if (isset($_POST["login"])) {
    $email_add = trim($_POST["email_add"]);
    $password = trim($_POST["password"]);

    if ($email_add === "admin" && $password === "admin123") {
        $_SESSION['user_type'] = "admin";
        header("Location: ../admin/admin.php");
        exit();
    }

    $sql = "SELECT * FROM user_info WHERE email_add = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email_add);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_info"] = $user;
        header("Location: ../users/user.php");
        exit();
    } else {
        $errorMessage = "Invalid email or password.";
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/index.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BenchNine:wght@300;400;700&family=Homenaje&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/haettenschweiler" rel="stylesheet">

    <link rel="icon" type="../bg/NU.png" href="../bg/NU.png">
    <title>NULooF Lost and Found System</title>
</head>

<body style="background-image: url(../bg/HOME_BG.png)">

    <div class="container">

        <!-- LEFT SECTION -->
        <!-- INFO SECTION -->
        <div class="info-section">
            <h1>
                <img src="../bg/NU.png" class="NU-Logo">
                NU LOST AND FOUND PORTAL
            </h1>

            <div class="feature">
                <div class="icon-container">
                    <i class="fas fa-thumbs-up"></i>
                </div>
                <div class="text-container">
                    <p class="feature-title">Easy to Use</p>
                    <p class="feature-desc">
                        You don’t need to be tech-savvy! Anyone can easily navigate and use the system without prior experience.
                    </p>
                </div>
            </div>

            <div class="feature">
                <div class="icon-container">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="text-container">
                    <p class="feature-title">Flexible</p>
                    <p class="feature-desc">
                        Allows users to report and claim lost items efficiently, with detailed tracking and easy access.
                    </p>
                </div>
            </div>

            <div class="feature">
                <div class="icon-container">
                    <i class="fas fa-users"></i>
                </div>
                <div class="text-container">
                    <p class="feature-title">Collaborative</p>
                    <p class="feature-desc">
                        Facilitates communication between students and faculty to ensure lost items are returned quickly.
                    </p>
                </div>
            </div>

            <p class="copyright">Copyright All Rights Reserved 2024</p>
        </div>

        <!-- RIGHT SECTION -->
        <!-- LOGIN FORM -->
        <div id="index" class="form-section">
            <div class="form-box">
                <h1>NU LOOF</h1>
                <h2>NATIONAL UNIVERSITY FAIRVIEW<br>LOST AND FOUND MANAGEMENT SYSTEM</h2>

                <!-- LOGIN FORM -->
                <form action="index.php" method="POST">

                    <div class="input-container">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="email_add" placeholder="Email Address" required>
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-eye-slash" id="toggleLoginPassword" onclick="toggleLoginPasswordVisibility()"></i>
                        <input type="password" name="password" id="loginpassword" placeholder="Password" required>
                    </div>

                    <div class="form-footer" style="margin-top: 0;">
                        <p class="links" id="forgotpassword"><a href="forgot_pw.php">Forgot Password?</a></p>
                    </div>

                    <!-- Form Footer for Button and Links -->
                    <div class="form-footer" style="align-items: center;">
                        <p class="links">Don't have an account yet? <a href="register.php">Create account</a></p>
                        <input type="submit" name="login" value="Login">
                    </div>
                </form>

                <?php
                    // Display error message if any
                    if ($errorMessage) {
                        echo "<div class='alert alert-danger'>$errorMessage</div>";
                    }
                ?>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const emailInput = document.querySelector("input[name='email_add']");
            const loginForm = document.querySelector("form[action='index.php']");

            // Create an alert message container below the form
            const errorMessage = document.createElement("div");
            errorMessage.classList.add("alert", "alert-danger");
            errorMessage.style.display = "none"; // Initially hidden

            // Insert the error message after the form
            loginForm.parentNode.insertBefore(errorMessage, loginForm.nextSibling);

            loginForm.addEventListener("submit", function (event) {
                const emailValue = emailInput.value.trim();

                if (emailValue !== "admin" && !emailValue.includes("@")) {
                    event.preventDefault();
                    errorMessage.textContent = "Please enter a valid email address.";
                    errorMessage.style.display = "block";
                } else {
                    errorMessage.style.display = "none";
                }
            });
        });

        // TOGGLE PASSWORD
        function toggleLoginPasswordVisibility() {
            const loginpasswordField = document.getElementById("loginpassword");
            const toggleEyeIcon = document.getElementById("toggleLoginPassword");

            if (loginpasswordField.type === "password") {
                loginpasswordField.type = "text";
                toggleEyeIcon.classList.remove("fa-eye-slash");
                toggleEyeIcon.classList.add("fa-eye");
            } else {
                loginpasswordField.type = "password";
                toggleEyeIcon.classList.remove("fa-eye");
                toggleEyeIcon.classList.add("fa-eye-slash");
            }
        }

    </script>

</body>
</html>
