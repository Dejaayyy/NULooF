<?php
// fetch_archive_report.php
include('../data/db.php'); // Include your database connection file

if (isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);

    // Fetch the report details, ensuring we include non-user reports
    $sql = "SELECT r.*, COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
            FROM reports_table r
            LEFT JOIN user_info u ON r.email_add = u.email_add
            WHERE r.report_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }

    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        echo json_encode(['success' => true, 'report' => $report]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Report not found']);
    }

    $stmt->close();
    $conn->close();
} else {
    error_log("Report ID not provided");
    echo json_encode(['success' => false, 'error' => 'Report ID not provided']);
}
?>
