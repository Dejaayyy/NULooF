<?php
include('../data/db.php');

$report_id = $_GET['report_id'];

$sql = "SELECT
        r.report_id,
        r.QR_CODE,
        r.ITEM_IMAGE,
        r.ITEM_NAME,
        r.ITEM_STATUS,
        r.HOLDING_STATUS,
        r.ITEM_DATE,
        r.ITEM_TIME,
        r.FLOOR_NUMBER,
        r.ROOM_NUMBER,
        r.ITEM_CATEGORY,
        r.ITEM_COLOR,
        r.ITEM_BRAND,
        r.ITEM_DESCRIPTION,
        r.STORAGE_LOCATION,
        COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
        FROM reports_table r
        LEFT JOIN user_info u ON r.email_add = u.email_add
        WHERE r.report_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Item Details | NULooF System</title>
    <link rel="icon" type="../bg/NU.png" href="../bg/NU.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            width: 60%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #111;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .detail-row > div {
            width: 48%;
        }

        .detail-label {
            font-weight: bold;
            color: #444;
        }

        .detail-value {
            margin-left: 10px;
            color: #555;
        }

        .image-container img {
            border: 2px solid #ddd;
            padding: 5px;
            border-radius: 5px;
            width: 150px;
            height: 150px;
        }

        .qr-code img {
            border: 2px solid #ddd;
            padding: 5px;
            border-radius: 5px;
        }

    </style>
</head>

<body>
    <div class="container">
        <h1>Report Details</h1>
        <?php if ($item): ?>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Item Image:</div>
                    <div class="image-container">
                        <img src="<?php echo htmlspecialchars($item['ITEM_IMAGE']); ?>" alt="Item Image">
                    </div>
                </div>

                <div>
                    <div class="detail-label">QR CODE:</div>
                    <div class="qr-code">
                        <img src="<?php echo htmlspecialchars($item['QR_CODE']); ?>" alt="QR Code" width="150">
                    </div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Report ID:</div>
                    <div class="detail-value"><?php echo $item['report_id']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Item Name:</div>
                    <div class="detail-value"><?php echo $item['ITEM_NAME']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Item Status:</div>
                    <div class="detail-value"><?php echo $item['ITEM_STATUS']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Holding Status:</div>
                    <div class="detail-value"><?php echo $item['HOLDING_STATUS']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Item Date:</div>
                    <div class="detail-value"><?php echo $item['ITEM_DATE']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Item Time:</div>
                    <div class="detail-value"><?php echo $item['ITEM_TIME']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Floor Number:</div>
                    <div class="detail-value"><?php echo $item['FLOOR_NUMBER']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Room Number:</div>
                    <div class="detail-value"><?php echo $item['ROOM_NUMBER']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Item Category:</div>
                    <div class="detail-value"><?php echo $item['ITEM_CATEGORY']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Item Color:</div>
                    <div class="detail-value"><?php echo $item['ITEM_COLOR']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Item Brand:</div>
                    <div class="detail-value"><?php echo $item['ITEM_BRAND']; ?></div>
                </div>
                <div>
                    <div class="detail-label">Item Description:</div>
                    <div class="detail-value"><?php echo $item['ITEM_DESCRIPTION']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Email Address:</div>
                    <div class="detail-value"><?php echo $item['email_add']; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Storage Located:</div>
                    <div class="detail-value"><?php echo $item['STORAGE_LOCATION']; ?></div>
                </div>
            </div>


        <?php else: ?>
            <p>Item not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
