<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
include('../data/db.php');

$errorMessage = '';

function sendOTP($email_add, $otp, $first_name, $last_name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nuloofairview@gmail.com';
        $mail->Password = 'hjue xwjp hutm zmyg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('nuloofairview@gmail.com', 'NU LOOF System');
        $mail->addAddress($email_add);
        $mail->isHTML(true);

        // Use HTML content for the email body
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "
        <h2>Welcome to the NU LOOF Lost and Found System!</h2>
        <h3>Hello, " . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "!</h3>
        <p>Thank you for registering in NU LooF System. Kindly please verify your account by entering the OTP code below:</p>
        <p>Your OTP Code: <strong>$otp</strong></p>
        <p>If you did not request this, please ignore this email.</p>
        <br>
        <p>- NU LooF System</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}


// REGISTRATION HANDLER
if (isset($_POST["register"])) {
    $id_number = trim($_POST["id_number"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $email_add = trim($_POST["email_add"]);
    $password = trim($_POST["password"]);
    $otp = rand(100000, 999999);
    $errors = array();

    if (empty($id_number) || empty($first_name) || empty($last_name) || empty($email_add) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (!preg_match('/^202[0-9]{7}$/', $id_number)) {
        $errors[] = "ID number must start with '202'";
    }

    if (!filter_var($email_add, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!preg_match('/@gmail\.com$/', $email_add)) {
        $errors[] = "Only @gmail.com email addresses are allowed.";
    }

    if (!preg_match("/^(?=.*[^a-zA-Z0-9]).{6,8}$/", $password)) {
        $errors[] = "Password must be 6-8 characters long and contain at least one special character.";
    }

    $sql = "SELECT id_number, email_add FROM user_info WHERE id_number = ? OR email_add = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $id_number, $email_add);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingUser = mysqli_fetch_assoc($result);

    if ($existingUser) {
        if ($existingUser['id_number'] === $id_number) {
            $errors[] = "ID Number already exists.";
        }
        if ($existingUser['email_add'] === $email_add) {
            $errors[] = "Email already exists.";
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO user_info (id_number, first_name, middle_name, last_name, email_add, password, otp) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $id_number, $first_name, $middle_name, $last_name, $email_add, $passwordHash, $otp);

        if (mysqli_stmt_execute($stmt) && sendOTP($email_add, $otp, $first_name, $last_name)) {
            $_SESSION['email_add'] = $email_add;
            header("Location: ../data/verify_otp.php");
            exit();
        } else {
            $_SESSION['errorMessage'] = "Registration failed. Please try again.";
            header("Location: register.php"); // Reload the page to show the error
            exit();
        }
    } else {
        $_SESSION['errorMessage'] = implode("<br>", $errors);
        header("Location: register.php"); // Reload to reflect errors
        exit();
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
    <title>NULooF System Registration Form</title>
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
        <!-- REGISTRATION FORM -->
        <div id="registration" class="form-section">
            <div class="form-box">
                <h1>NU LOOF</h1>
                <h2>NATIONAL UNIVERSITY FAIRVIEW<br>LOST AND FOUND MANAGEMENT SYSTEM</h2>

                <!-- REGISTRATION FORM -->
                <form action="register.php" method="POST">

                    <div class="input-container">
                        <i class="fa-solid fa-id-card"></i>
                        <input type="text" name="id_number" placeholder="ID Number" required>
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <input type="text" name="first_name" placeholder="First Name" required>
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <input type="text" name="middle_name" placeholder="Middle Name (Optional)">
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-user"></i>
                        <input type="email" name="email_add" placeholder="Email Address" required>
                        </div>

                    <div class="input-container">
                        <i class="fa-solid fa-eye-slash" id="togglePassword" onclick="togglePasswordVisibility()"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                    </div>

                    <!-- Form Footer for Button and Links -->
                    <div class="form-footer" style="align-items: center;">
                        <p class="links">Already have an account? <a href="index.php">Login here</a></p>
                        <input type="submit" name="register" value="Register">
                    </div>
                </form>

                <?php
                    // Display error message if any
                    if (!empty($_SESSION['errorMessage'])) {
                        echo "<div class='alert alert-danger'>" . $_SESSION['errorMessage'] . "</div>";
                        unset($_SESSION['errorMessage']); // Clear the error message after displaying it
                    }
                ?>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Create an alert message container below the form
            const errorMessage = document.createElement("div");
            errorMessage.classList.add("alert", "alert-danger");
            errorMessage.style.display = "none"; // Initially hidden
        });
        
        // TOGGLE PASSWORD
        function togglePasswordVisibility() {
            const passwordField = document.getElementById("password");
            const toggleIcon = document.getElementById("togglePassword");

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
