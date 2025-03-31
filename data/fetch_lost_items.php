<?php
include('db.php');

if (isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];

    // Get the lost item and user info
    $sql = "SELECT r.*, u.email_add 
            FROM reports_table r 
            JOIN user_info u 
            ON r.email_add = u.email_add 
            WHERE r.report_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $foundItem = $result->fetch_assoc();

    // Fetch found items with the same category
    $sql = "SELECT r.*, u.id_number as REPORTED_BY 
            FROM reports_table r 
            JOIN user_info u 
            ON r.email_add = u.email_add
            WHERE r.ITEM_STATUS = 'lost' AND r.ITEM_CATEGORY = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $foundItem['ITEM_CATEGORY']);
    $stmt->execute();
    $result = $stmt->get_result();

    $lostItems = [];
    while ($row = $result->fetch_assoc()) {
        $lostItems[] = $row;
    }

    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'userInfo' => $foundItem,
        'lostItems' => $lostItems
    ]);
}

$conn->close();
?>