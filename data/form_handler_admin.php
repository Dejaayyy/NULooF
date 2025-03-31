<?php
include("../data/db.php");
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../users/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['status', 'itemName', 'itemCategory', 'itemColor',
                        'itemBrand', 'description', 'floorNo', 'roomNo',
                        'reportDate', 'reportTime', 'nonUserEmail'];

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
            // Proceed with database insertion
        } else {
            error_log("Error moving uploaded file to $folder");
            echo "Error uploading image.";
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
    $nonUserEmail = mysqli_real_escape_string($conn, $_POST['nonUserEmail']);

    // Generate QR Code
    $qrCodeData = "Item Name: $itemName
    \nCategory: $itemCategory
    \nColor: $itemColor
    \nBrand: $itemBrand
    \nDescription: $description
    \nLocation: Floor $floorNo, $roomNo
    \nDate: $reportDate\nTime: $reportTime
    \nReported by: $nonUserEmail";
    $qrCodeFilePath = $uploadDir . 'qrcode_' . time() . '.png';

    // Save QR Code as an image
    file_put_contents($qrCodeFilePath, file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrCodeData)));

    // Insert the data into the database
    $query = "INSERT INTO `reports_table`
    (`ITEM_IMAGE`, `ITEM_STATUS`, `HOLDING_STATUS`, `ITEM_NAME`, `ITEM_CATEGORY`,
    `ITEM_COLOR`, `ITEM_BRAND`, `ITEM_DESCRIPTION`, `FLOOR_NUMBER`, `ROOM_NUMBER`,
    `ITEM_DATE`, `ITEM_TIME`, `non_user_email`, `QR_CODE`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssssssssssssss',
        $folder, $status, $holding, $itemName, $itemCategory, $itemColor,
        $itemBrand, $description, $floorNo, $roomNo, $reportDate,
        $reportTime, $nonUserEmail, $qrCodeFilePath);

    if (mysqli_stmt_execute($stmt)) {
        echo "Report submitted successfully!";
    } else {
        error_log("Error executing query: " . mysqli_stmt_error($stmt));
        echo "Error: Unable to submit report.";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "Form not submitted properly!";
}

mysqli_close($conn);
?>
