<?php
include("../data/db.php");
require '../vendor/autoload.php'; // Ensure PHPMailer is installed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_info']['email_add'])) {
    echo "No user logged in.";
    header("Location: ../users/index.php");
    exit;
}

// Use the email from the session
$userEmail = $_SESSION['user_info']['email_add'];
$userName = $_SESSION['user_info']['first_name'] . ' ' . $_SESSION['user_info']['last_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['status', 'itemName', 'itemCategory', 'itemColor',
                        'itemBrand', 'description', 'floorNo', 'roomNo',
                        'reportDate', 'reportTime'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo "Error: Missing required field - $field";
            exit;
        }
    }

    // Define the upload directory relative to the root
    $uploadDir = '../images/';

    // Create the upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Handle file upload
    $folder = '';
    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
        $itemImage = $_FILES['itemImage']['name'];
        $tempname = $_FILES['itemImage']['tmp_name'];
        $folder = $uploadDir . $itemImage;

        if (move_uploaded_file($tempname, $folder)) {
            echo "File uploaded successfully. ";
        } else {
            echo "Error uploading image: Unable to move file to $folder";
            exit;
        }
    } else {
        echo "File upload error: " . $_FILES['itemImage']['error'];
        exit;
    }

    // Sanitize other inputs
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $holding = isset($_POST['holding']) ? mysqli_real_escape_string($conn, $_POST['holding']) : '';
    $itemName = mysqli_real_escape_string($conn, $_POST['itemName']);

    $itemCategory = mysqli_real_escape_string($conn, $_POST['itemCategory']);
    if ($itemCategory === "Others" && !empty($_POST['customCategory'])) {
        $itemCategory = mysqli_real_escape_string($conn, $_POST['customCategory']);
    }

    $itemColor = mysqli_real_escape_string($conn, $_POST['itemColor']);
    if ($itemColor === "Others" && !empty($_POST['customColor'])) {
        $itemColor = mysqli_real_escape_string($conn, $_POST['customColor']);
    }

    $itemBrand = mysqli_real_escape_string($conn, $_POST['itemBrand']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $floorNo = mysqli_real_escape_string($conn, $_POST['floorNo']);

    $roomNo = mysqli_real_escape_string($conn, $_POST['roomNo']);
    $otherRoom = isset($_POST['otherRoom']) ? mysqli_real_escape_string($conn, $_POST['otherRoom']) : "";
    // If "Other" was selected, store the custom input instead
    if ($roomNo === "Other" && !empty($otherRoom)) {
        $roomNo = $otherRoom;
    }
    $reportDate = mysqli_real_escape_string($conn, $_POST['reportDate']);
    $reportTime = mysqli_real_escape_string($conn, $_POST['reportTime']);

    // Generate QR Code
    $qrCodeData = "Item Name: $itemName
    \nCategory: $itemCategory
    \nColor: $itemColor
    \nBrand: $itemBrand
    \nDescription: $description
    \nLocation: $floorNo Floor, $roomNo Room
    \nDate: $reportDate\nTime: $reportTime
    \nReported by: $userEmail";
    $qrCodeFilePath = $uploadDir . 'qrcode_' . time() . '.png';

    // Save QR Code as an image
    file_put_contents($qrCodeFilePath, file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrCodeData)));

    // Insert the data into the database
    $query = "INSERT INTO `reports_table`
        (`ITEM_IMAGE`, `ITEM_STATUS`, `HOLDING_STATUS`, `ITEM_NAME`, `ITEM_CATEGORY`,
         `ITEM_COLOR`, `ITEM_BRAND`, `ITEM_DESCRIPTION`, `FLOOR_NUMBER`, `ROOM_NUMBER`, 
         `ITEM_DATE`, `ITEM_TIME`, `email_add`, `QR_CODE`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssssssssssssss',
        $folder, $status, $holding, $itemName, $itemCategory, $itemColor,
        $itemBrand, $description, $floorNo, $roomNo, $reportDate,
        $reportTime, $userEmail, $qrCodeFilePath);

    if (mysqli_stmt_execute($stmt)) {
        echo "Report submitted successfully!";

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nuloofairview@gmail.com';
            $mail->Password = 'hjue xwjp hutm zmyg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('your_email@example.com', 'NULooF System');
            $mail->addAddress($userEmail, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Report Submission Confirmation';
            $mail->Body = '<p>Dear ' . htmlspecialchars($userName) . ',</p>
                            <p>Your report has been successfully submitted.</p>
                            <p>Here are the details:</p>
                            <p><strong>Item Name:</strong> ' . htmlspecialchars($itemName) . '</p>
                            <p><strong>Category:</strong> ' . htmlspecialchars($itemCategory) . '</p>
                            <p><strong>Color:</strong> ' . htmlspecialchars($itemColor) . '</p>
                            <p><strong>Brand:</strong> ' . htmlspecialchars($itemBrand) . '</p>
                            <p><strong>Description:</strong> ' . htmlspecialchars($description) . '</p>
                            <p><strong>Location:</strong> ' . htmlspecialchars($floorNo) . ' Floor, ' . htmlspecialchars($roomNo) . ' Room</p>
                            <p><strong>Date:</strong> ' . htmlspecialchars($reportDate) . '</p>
                            <p><strong>Time:</strong> ' . htmlspecialchars($reportTime) . '</p>
                            <p>Kindly keep this email for your reference.</p>
                            <p>Thank you for using our service.</p>
                            <p>Best regards,<br>NULooF Team</p>';

            $mail->send();
            echo ' Confirmation email has been sent.';
        } catch (Exception $e) {
            echo " Confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "Form not submitted properly!";
}

mysqli_close($conn);
?>