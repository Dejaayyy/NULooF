<?php
include('../data/db.php');
session_start();

if (!isset($_SESSION['email_add'])) {
    header("Location: ../users/index.php");
    exit();
}

$email_add = $_SESSION['email_add'];
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_entered = $_POST["otp"];

    // Check OTP from the database
    $stmt = $conn->prepare("SELECT otp FROM user_info WHERE email_add = ? AND verified = 0");
    $stmt->bind_param("s", $email_add);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($otp);
        $stmt->fetch();
        
        if ($otp_entered == $otp) {
            // Update verified status
            $update_stmt = $conn->prepare("UPDATE user_info SET verified = 1 WHERE email_add = ?");
            $update_stmt->bind_param("s", $email_add);
            $update_stmt->execute();
            $update_stmt->close();
            
            $_SESSION["verified"] = true;
            header("Location: ../index.php");
            exit();
        } else {
            $errorMessage = "Invalid OTP. Please try again.";
        }
    } else {
        $errorMessage = "Email not found or already verified.";
    }

    $stmt->close();
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
    <title>Verify OTP</title>
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
                <h1 style="font-size: 55px;">Verify Your Email</h1>
                <p>Enter the OTP sent to <strong><?php echo htmlspecialchars($email_add); ?></strong></p>

                <form action="verify_otp.php" method="POST">

                    <div class="input-container">
                        <i class="fa-solid fa-key"></i>
                        <input type="text" name="otp" placeholder="Enter OTP" required>
                    </div>

                    <!-- Form Footer for Button and Links -->
                    <div class="form-footer" style="justify-content: flex-end;">
                        <button type="submit" name="verify">Verify</button>
                    </div>

                </form>

                <?php 
                    if (!empty($errorMessage)) 
                        echo "<p class='error'>$errorMessage</p>"; 
                ?>

            </div>
        </div>

    </div>

</body>
</html>
