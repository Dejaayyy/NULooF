<?php
require 'db.php'; // Ensure this connects to your database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $report_id = $_POST["report_id"];
    $matched_with = $_POST["matched_with"]; // Assuming you are sending this value from the client

    // Update database with verification status
    $query = "UPDATE reports_table
              SET HOLDING_STATUS = 'Retrieved', match_status = 'matched', matched_with = ?
              WHERE report_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $matched_with, $report_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
