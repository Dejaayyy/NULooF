<?php
include('db.php');

if (isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);

    // Fetch matched reports
    $query = "
        SELECT
            a.report_id as report_id_a,
            b.report_id as report_id_b,
            a.ITEM_STATUS as status_a,
            b.ITEM_STATUS as status_b,
            a.ITEM_IMAGE as item_image_a,
            b.ITEM_IMAGE as item_image_b,
            a.ITEM_NAME as item_name_a,
            b.ITEM_NAME as item_name_b,
            a.QR_CODE as qr_code_a,
            b.QR_CODE as qr_code_b,
            COALESCE(ua.email_add, a.non_user_email, a.email_add) as reporter1_email_add,
            COALESCE(ub.email_add, b.non_user_email, b.email_add) as reporter2_email_add,
            a.ITEM_CATEGORY as category_a,
            a.ITEM_COLOR as color_a,
            a.ITEM_BRAND as brand_a,
            a.ITEM_DESCRIPTION as description_a,
            a.FLOOR_NUMBER as floor_a,
            a.ROOM_NUMBER as room_a,
            a.ITEM_DATE as date_a,
            b.ITEM_CATEGORY as category_b,
            b.ITEM_COLOR as color_b,
            b.ITEM_BRAND as brand_b,
            b.ITEM_DESCRIPTION as description_b,
            b.FLOOR_NUMBER as floor_b,
            b.ROOM_NUMBER as room_b,
            b.ITEM_DATE as date_b
        FROM reports_table a
        JOIN reports_table b ON a.matched_with = b.report_id
        LEFT JOIN user_info ua ON a.email_add = ua.email_add
        LEFT JOIN user_info ub ON b.email_add = ub.email_add
        WHERE (a.report_id = ? OR b.report_id = ?)
        AND (a.match_status IN ('matched', 'Matching') OR b.match_status IN ('matched', 'Matching'))
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }

    $stmt->bind_param("ii", $report_id, $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Determine which item is lost and which is found
        $isA_Lost = ($row['status_a'] === 'lost');

        $data = [
            'success' => true,
            'data' => [
                'lost_report_id' => $isA_Lost ? $row['report_id_a'] : $row['report_id_b'],
                'found_report_id' => $isA_Lost ? $row['report_id_b'] : $row['report_id_a'],
                'lost_qr_code' => $isA_Lost ? $row['qr_code_a'] : $row['qr_code_b'],
                'found_qr_code' => $isA_Lost ? $row['qr_code_b'] : $row['qr_code_a'],
                'lost_item_image' => $isA_Lost ? $row['item_image_a'] : $row['item_image_b'],
                'found_item_image' => $isA_Lost ? $row['item_image_b'] : $row['item_image_a'],
                'lost_item_name' => $isA_Lost ? $row['item_name_a'] : $row['item_name_b'],
                'found_item_name' => $isA_Lost ? $row['item_name_b'] : $row['item_name_a'],
                'reporter1_email_add' => $isA_Lost ? $row['reporter1_email_add'] : $row['reporter2_email_add'],
                'reporter2_email_add' => $isA_Lost ? $row['reporter2_email_add'] : $row['reporter1_email_add'],

                // Assign lost item details correctly
                'ITEM_CATEGORY' => $isA_Lost ? $row['category_a'] : $row['category_b'],
                'ITEM_COLOR' => $isA_Lost ? $row['color_a'] : $row['color_b'],
                'ITEM_BRAND' => $isA_Lost ? $row['brand_a'] : $row['brand_b'],
                'FLOOR_NUMBER' => $isA_Lost ? $row['floor_a'] : $row['floor_b'],
                'ROOM_NUMBER' => $isA_Lost ? $row['room_a'] : $row['room_b'],
                'ITEM_DATE' => $isA_Lost ? $row['date_a'] : $row['date_b'],
                'ITEM_DESCRIPTION' => $isA_Lost ? $row['description_a'] : $row['description_b'],

                // Assign found item details correctly
                'paired_category' => !$isA_Lost ? $row['category_a'] : $row['category_b'],
                'paired_color' => !$isA_Lost ? $row['color_a'] : $row['color_b'],
                'paired_brand' => !$isA_Lost ? $row['brand_a'] : $row['brand_b'],
                'paired_floor' => !$isA_Lost ? $row['floor_a'] : $row['floor_b'],
                'paired_room' => !$isA_Lost ? $row['room_a'] : $row['room_b'],
                'paired_date' => !$isA_Lost ? $row['date_a'] : $row['date_b'],
                'paired_description' => !$isA_Lost ? $row['description_a'] : $row['description_b']
            ]
        ];

        echo json_encode($data);
    } else {
        error_log("No matched items found for report_id: $report_id");
        echo json_encode(['success' => false, 'error' => 'No matched items found']);
    }

    $stmt->close();
    $conn->close();
} else {
    error_log("Report ID not set");
    echo json_encode(['success' => false, 'error' => 'Report ID not set']);
}
?>
