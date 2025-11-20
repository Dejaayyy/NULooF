<?php
// fetch_archive_section.php
include('../data/db.php'); // Include your database connection file

// Fetch archived reports
$archived_query = "SELECT r.report_id, r.QR_CODE, r.ITEM_IMAGE, r.ITEM_NAME, r.ITEM_STATUS, r.HOLDING_STATUS, r.ITEM_DATE, r.ITEM_TIME,
                   r.FLOOR_NUMBER, r.ROOM_NUMBER, r.ITEM_CATEGORY, r.ITEM_COLOR, r.ITEM_BRAND, r.ITEM_DESCRIPTION,
                   COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
            FROM reports_table r
            JOIN user_info u ON r.email_add = u.email_add
            WHERE r.status = 'archived'";

$archived_result = $conn->query($archived_query);

// Store results in an array
$archivedItems = [];
if ($archived_result->num_rows > 0) {
    while($row = $archived_result->fetch_assoc()) {
        $archivedItems[] = $row;
    }
} else {
    $archivedItems = []; // No archived items
}

// Close the database connection
mysqli_close($conn);
?>