<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');

include("db.php");
session_start();

function sendResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }

    // Retrieve form data
    $itemId = $_POST['itemId'];
    $status = $_POST['status'];
    $holding = $_POST['holding'];
    $itemName = $_POST['itemName'];
    $itemCategory = $_POST['itemCategory'];
    $itemColor = $_POST['itemColor'];
    $itemBrand = $_POST['itemBrand'];
    $description = $_POST['description'];
    $floorNo = $_POST['floorNo'];
    $roomNo = $_POST['roomNo'];
    $reportDate = $_POST['reportDate'];
    $reportTime = $_POST['reportTime'];
    $userEmail = $_POST['editUserEmail'];
    $storageLocation = $_POST['editstorageRoom'];

    // Handle file upload
    $folder = '../images/';
    $itemImage = '';

    if (!empty($_FILES['itemImage']['name'])) {
        $itemImage = $folder . basename($_FILES['itemImage']['name']);
        if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $itemImage)) {
            throw new Exception("Failed to upload image.");
        }
    } else {
        // Use existing image if no new image is uploaded
        $itemImage = $_POST['existingImage'];
    }

    // Prepare the SQL query
    $sql = "UPDATE reports_table SET
            ITEM_IMAGE = ?,
            ITEM_STATUS = ?,
            HOLDING_STATUS = ?,
            ITEM_NAME = ?,
            ITEM_CATEGORY = ?,
            ITEM_COLOR = ?,
            ITEM_BRAND = ?,
            ITEM_DESCRIPTION = ?,
            FLOOR_NUMBER = ?,
            ROOM_NUMBER = ?,
            ITEM_DATE = ?,
            ITEM_TIME = ?,
            STORAGE_LOCATION = ?
            WHERE report_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param('sssssssssssssi',
        $itemImage,
        $status,
        $holding,
        $itemName,
        $itemCategory,
        $itemColor,
        $itemBrand,
        $description,
        $floorNo,
        $roomNo,
        $reportDate,
        $reportTime,
        $storageLocation,
        $itemId
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        sendResponse(true, 'Report updated successfully');
    } else {
        sendResponse(false, 'No changes made or report not found');
    }

} catch (Exception $e) {
    error_log("Error in edit_form_handler: " . $e->getMessage());
    sendResponse(false, 'Error: ' . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
