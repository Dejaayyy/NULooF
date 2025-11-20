<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ensure PHPMailer is installed
include('../data/db.php'); // Database connection

header("Content-Type: application/json");

// Fetch input data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['report_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request. Report ID missing."]);
    exit;
}

$reportId = intval($data['report_id']); // Ensure it's an integer

// Fetch item details along with email
$sql = "SELECT 
            COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add,
            r.ITEM_NAME, r.ITEM_BRAND, r.ITEM_COLOR, r.ITEM_CATEGORY, 
            r.ITEM_DESCRIPTION, r.ITEM_DATE
        FROM reports_table r
        LEFT JOIN user_info u ON r.email_add = u.email_add
        WHERE r.report_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $reportId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Report not found."]);
    exit;
}

$email_add = $row['email_add'];
$itemName = $row['ITEM_NAME'];
$itemBrand = $row['ITEM_BRAND'];
$itemColor = $row['ITEM_COLOR'];
$itemCategory = $row['ITEM_CATEGORY'];
$itemDescription = $row['ITEM_DESCRIPTION'];
$itemDate = $row['ITEM_DATE'];

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nuloofairview@gmail.com';
    $mail->Password = 'hjue xwjp hutm zmyg'; // Ensure this is secure!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Sender and Recipient
    $mail->setFrom('nuloofairview@gmail.com', 'NU LOOF System');
    $mail->addAddress($email_add);

    // Email Content
    $mail->isHTML(true);
    $mail->Subject = "Action Required: Lost Item Retrieval Notification";
    $mail->Body = "
        <p>Dear Owner,</p>
        <p>We're notifying you that the following lost item must be retrieved from the Discipline Office:</p>
        <p><strong>Item Name:</strong> $itemName</p>
        <p><strong>Brand:</strong> $itemBrand</p>
        <p><strong>Color:</strong> $itemColor</p>
        <p><strong>Category:</strong> $itemCategory</p>
        <p><strong>Description:</strong> $itemDescription</p>
        <p><strong>Date Lost:</strong> $itemDate</p>
        
        <p>Please collect this item at your earliest convenience.</p>
        <p>Thank you.</p>
        <br>
        <p><strong>NULooF - Lost and Found System</strong></p>
    ";

    // Send email
    if ($mail->send()) {
        echo json_encode(["status" => "success", "message" => "Notification sent successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send email."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
}

// Close connections
$stmt->close();
$conn->close();
?>
