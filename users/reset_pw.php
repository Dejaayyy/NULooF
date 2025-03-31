<?php
session_start();
include('../data/db.php');

$errorMessage = "";
$successMessage = "";

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verify if the token exists and is still valid
    $sql = "SELECT email_add FROM user_info WHERE reset_token = ? AND reset_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        // Store error message in session
        $_SESSION['error_message'] = "Invalid or expired token. Please request a new password reset.";
        header("Location: ../users/forgot_pw.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "No token provided.";
    header("Location: ../users/forgot_pw.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($new_password) || empty($confirm_password)) {
        $errorMessage = "Please fill in all fields.";

    } elseif ($new_password !== $confirm_password) {
        $errorMessage = "Passwords do not match.";

    } elseif (strlen($new_password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email_add = $user["email_add"];

        // Update password in database and clear token
        $sql = "UPDATE user_info SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email_add = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email_add);
        
        if (mysqli_stmt_execute($stmt)) {
            $successMessage = "Password reset successful. You can now <a href='login.php'>log in</a>.";
        } else {
            $errorMessage = "Something went wrong. Please try again.";
        }
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
    <title>Reset Password</title>
</head>

<style>
    .form-box button[type="submit"] {
        width: auto;
        padding: 10px;
        font-family: 'Arial Narrow', Arial, sans-serif;
        font-weight: 200;
        font-size: 16px;
        border: none;
        border-radius: 4px;
        background-color: #ffffff;
        color: #2d388a;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-box button[type="submit"]:hover {
        background-color: #FFC700;
        color: #2d388a;
    }

    .input-container input {
        font-family: 'Arial Narrow', Arial, sans-serif;
        width: 100%;
        border: none;
        padding: 10px;
        padding-left: 10px;
        font-size: 18px;
        outline: none;
        background-color: transparent;
    }

</style>

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
        <!-- RESET PW FORM -->
        <div class="form-section">
            <div class="form-box">
                <h1 style="font-size: 55px;">Reset Password</h1>
                <p>Enter your new password, it must contain 6-8 characters with at least one special character.</p>

                <!-- RESET PW FORM -->
                <form action="reset_pw.php" method="POST">
                    <div class="input-container">
                        <i class="fa-solid fa-eye-slash" id="toggleNewPassword" onclick="toggleNewPasswordVisibility()"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter New Password" required>
                    </div>

                    <div class="input-container">
                        <i class="fa-solid fa-eye-slash" id="toggleConfirmPassword" onclick="toggleConfirmPasswordVisibility()"></i>
                        <input type="password" name="confirm_password" id="confirm_password"placeholder="Confirm New Password" required>
                    </div>

                    <div class="form-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <p class="links" style="font-size: medium;"><a href="index.php">Back to login?</a></p>
                        <button type="submit">Reset Password</button>
                    </div>

                </form>

                <?php 
                    if (!empty($errorMessage)) echo "<div class='alert alert-danger'>$errorMessage</div>";
                    if (!empty($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>";
                ?>

            </div>

        </div>

    </div>

    <script>
        // TOGGLE NEW PASSWORD
        function toggleNewPasswordVisibility() {
            const newpasswordField = document.getElementById("new_password");
            const toggleEyeIcon = document.getElementById("toggleNewPassword");

            if (newpasswordField.type === "password") {
                newpasswordField.type = "text";
                toggleEyeIcon.classList.remove("fa-eye-slash");
                toggleEyeIcon.classList.add("fa-eye");
            } else {
                newpasswordField.type = "password";
                toggleEyeIcon.classList.remove("fa-eye");
                toggleEyeIcon.classList.add("fa-eye-slash");
            }
        }

        // TOGGLE CONFIRM PASSWORD
        function toggleConfirmPasswordVisibility() {
            const passwordField = document.getElementById("confirm_password");
            const toggleIcon = document.getElementById("toggleConfirmPassword");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            }
        }


    </script>

</body>
</html>