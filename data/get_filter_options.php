<?php
include('db.php');

$field = $_GET['field'] ?? '';
$validFields = ['ITEM_CATEGORY', 'ITEM_COLOR', 'FLOOR_NUMBER', 'ROOM_NUMBER'];

if (!in_array($field, $validFields)) {
    echo json_encode(['error' => 'Invalid field']);
    exit;
}

$query = "SELECT DISTINCT $field FROM reports_table WHERE $field IS NOT NULL ORDER BY $field";
$result = $conn->query($query);

$options = [];
while ($row = $result->fetch_assoc()) {
    $options[] = $row[$field];
}

header('Content-Type: application/json');
echo json_encode(['options' => $options]);
$conn->close();
?>