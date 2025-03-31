<?php
// retrieve_report.php
include('../data/db.php'); // Include your database connection file

if (isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);

    // Update the report status to 'active'
    $sql = "UPDATE reports_table SET status = 'active' WHERE report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
