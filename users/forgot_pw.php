<?php
session_start();

if (isset($_SESSION['error_message'])) {
    echo "<div style='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the session message after displaying
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
include('../data/db.php');

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_add = trim($_POST["email_add"]);

    // Check if email exists in user_info table
    $sql = "SELECT * FROM user_info WHERE email_add = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email_add);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $errors = array();

    if (!filter_var($email_add, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!preg_match('/@gmail\.com$/', $email_add)) {
        $errors[] = "Only @gmail.com email addresses are allowed.";
    }

    if ($user) {
        // Generate reset token and expiry
        $token = md5(uniqid(rand(), true));
        date_default_timezone_set('Asia/Manila');
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store the token in the database
        $sql = "UPDATE user_info SET reset_token=?, reset_expiry=? WHERE email_add=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $token, $expiry, $email_add);
        mysqli_stmt_execute($stmt);


        // Password reset link
        $reset_link = "https://localhost/nuloof/users/reset_pw.php?token=$token";

        // Send reset email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nuloofairview@gmail.com';
            $mail->Password = 'hjue xwjp hutm zmyg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('nuloofairview@gmail.com', 'NU LOOF System');
            $mail->addAddress($email_add);
            $mail->isHTML(true);

            $mail->Subject = 'Password Reset Request';
            $mail->Body = "<p>Click the link below to reset your password:</p>
                           <a href='$reset_link'>Reset Password</a>
                           <p>This link will expire in 1 hour.</p>";

            $mail->send();
            $successMessage = "Password reset link has been sent to your email.";
        } catch (Exception $e) {
            $errorMessage = "Error sending email: {$mail->ErrorInfo}";
        }
    } else {
        $errorMessage = "Email not found.";
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
    <title>Forgot Password</title>
</head>

<style>
    .form-box button[type="submit"] {
        width: 110px;
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
        <!-- VERIFY OTP FORM -->
        <div class="form-section">
            <div class="form-box">
                <h1 style="font-size: 55px;">Forgot Password?</h1>
                <p>Enter your email address and we'll send you a link to reset your password.</p>

                <form action="forgot_pw.php" method="POST">

                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email_add" name="email_add" placeholder="Enter Email Address" required>
                    </div>

                    <!-- Form Footer for Buttons and Links -->
                    <div class="form-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <p class="links" style="font-size: medium;"><a href="index.php">Back to login?</a></p>
                        <!-- <a href="index.php" class="btn btn-secondary" style="text-decoration: none; padding: 10px 20px; border-radius: 4px; background-color: #6c757d; color: white; border: none;">Back to Home</a> -->
                        <button type="submit" name="submit">Submit</button>
                    </div>

                </form>

                <?php 
                    if (!empty($errorMessage)) echo "<div class='alert alert-danger'>$errorMessage</div>";
                    if (!empty($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>";
                ?>

            </div>
        </div>

    </div>

</body>
</html>
