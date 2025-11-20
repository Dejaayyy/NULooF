<?php
require 'db.php'; // Ensure this connects to your database

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if required POST variables are set
    if (!isset($_POST["report_id"], $_POST["holding_status"], $_POST["storage_location"])) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit();
    }

    $report_id = $_POST["report_id"];
    $holding_status = $_POST["holding_status"];
    $storage_location = $_POST["storage_location"];

    error_log("Received Data: " . print_r($_POST, true)); // Debugging log

    // Fetch report details based on report_id
    $fetch_query = "SELECT r.ITEM_NAME, r.ITEM_CATEGORY, r.ITEM_COLOR, r.ITEM_BRAND, r.ITEM_DESCRIPTION, 
                           r.FLOOR_NUMBER, r.ROOM_NUMBER, r.ITEM_DATE, r.ITEM_TIME, 
                           COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                    FROM reports_table r
                    LEFT JOIN user_info u ON r.email_add = u.email_add
                    WHERE r.report_id = ?";

    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Report not found."]);
        exit();
    }

    $report = $result->fetch_assoc();
    $stmt->close();

    // Format the QR code data string
    $qr_data = "\nItem Name: " . $report["ITEM_NAME"] .
               "\nCategory: " . $report["ITEM_CATEGORY"] .
               "\nColor: " . $report["ITEM_COLOR"] .
               "\nBrand: " . $report["ITEM_BRAND"] .
               "\nDescription: " . $report["ITEM_DESCRIPTION"] .
               "\nReported by: " . $report["email_add"] .
               "\nStorage Room: " . $storage_location;

    // Generate QR code URL
    $updated_qr_code = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qr_data);

    // Update the report with new QR code and verification details
    $update_query = "UPDATE reports_table 
                     SET HOLDING_STATUS = ?, STORAGE_LOCATION = ?, QR_CODE = COALESCE(?, QR_CODE), VERIFIED_STATUS = 1 
                     WHERE report_id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $holding_status, $storage_location, $updated_qr_code, $report_id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "updated_qr" => $updated_qr_code,
            "report_details" => $report
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }

    $stmt->close();
    $conn->close();
}
?>
