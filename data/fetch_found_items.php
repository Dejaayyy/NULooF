<?php
include('db.php');

if (isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    
    error_log("Fetching lost item details for report ID: $report_id");

    // Get the lost item and user info
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
    $lostItem = $result->fetch_assoc();

    if (!$lostItem) {
        error_log("No lost item found for report ID: $report_id");
        echo json_encode(['success' => false, 'error' => 'No lost item found']);
        exit;
    }

    // Fetch found items that match category, color, or brand
    $sql = "SELECT r.*, COALESCE(u.email_add, r.non_user_email, r.email_add) AS REPORTED_BY
            FROM reports_table r
            LEFT JOIN user_info u ON r.email_add = u.email_add
            WHERE r.ITEM_STATUS = 'found'
            AND r.status != 'archived'
            AND (
                r.ITEM_CATEGORY = ?
                OR r.ITEM_COLOR = ?
                OR r.ITEM_BRAND = ?
            )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }

    $stmt->bind_param("sss", $lostItem['ITEM_CATEGORY'], $lostItem['ITEM_COLOR'], $lostItem['ITEM_BRAND']);
    $stmt->execute();
    $result = $stmt->get_result();

    $foundItems = [];
    while ($row = $result->fetch_assoc()) {
        $foundItems[] = $row;
    }

    error_log("Found " . count($foundItems) . " matching found items");

    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'userInfo' => $lostItem,
        'foundItems' => $foundItems
    ]);

} else {
    error_log("Report ID not set");
    echo json_encode(['success' => false, 'error' => 'Report ID not set']);
}

$conn->close();
?>
