<?php
include('db.php');
require '../vendor/autoload.php'; // Ensure PHPMailer is installed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lostReportId = intval($_POST['lost_report_id']);
    $foundReportId = intval($_POST['found_report_id']);

    error_log("Attempting to match lost report ID $lostReportId with found report ID $foundReportId");

    // Start transaction
    $conn->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);

    try {
        // Fetch lost item details
        $queryLost = "SELECT ITEM_NAME, ITEM_BRAND, ITEM_COLOR, ITEM_CATEGORY, ITEM_DESCRIPTION, ITEM_DATE, 
                      COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                      FROM reports_table r LEFT JOIN user_info u ON r.email_add = u.email_add WHERE report_id = ?";
        $stmtLost = $conn->prepare($queryLost);
        $stmtLost->bind_param("i", $lostReportId);
        $stmtLost->execute();
        $stmtLost->bind_result($lostItemName, $lostBrand, $lostColor, $lostCategory, $lostDescription, $lostDate, $lostEmail);
        $stmtLost->fetch();
        $stmtLost->close();

        // Fetch found item details
        $queryFound = "SELECT ITEM_NAME, ITEM_BRAND, ITEM_COLOR, ITEM_CATEGORY, ITEM_DESCRIPTION, ITEM_DATE, 
                       COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                       FROM reports_table r LEFT JOIN user_info u ON r.email_add = u.email_add WHERE report_id = ?";
        $stmtFound = $conn->prepare($queryFound);
        $stmtFound->bind_param("i", $foundReportId);
        $stmtFound->execute();
        $stmtFound->bind_result($foundItemName, $foundBrand, $foundColor, $foundCategory, $foundDescription, $foundDate, $foundEmail);
        $stmtFound->fetch();
        $stmtFound->close();

        // Validate fetched data
        if (!$lostEmail || !$foundEmail) {
            throw new Exception("Matching failed: One or both reports not found.");
        }

        // Update lost item
        $queryUpdateLost = "UPDATE reports_table SET match_status = 'Matching', matched_with = ? WHERE report_id = ?";
        $stmtUpdateLost = $conn->prepare($queryUpdateLost);
        $stmtUpdateLost->bind_param("ii", $foundReportId, $lostReportId);
        $stmtUpdateLost->execute();
        $stmtUpdateLost->close();

        // Update found item
        $queryUpdateFound = "UPDATE reports_table SET match_status = 'Matched', matched_with = ? WHERE report_id = ?";
        $stmtUpdateFound = $conn->prepare($queryUpdateFound);
        $stmtUpdateFound->bind_param("ii", $lostReportId, $foundReportId);
        $stmtUpdateFound->execute();
        $stmtUpdateFound->close();

        // Commit transaction
        $conn->commit();

        // Function to send email
        function sendNotification($email, $subject, $body) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nuloofairview@gmail.com';
                $mail->Password = 'hjue xwjp hutm zmyg'; // Use environment variables for security
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                
                $mail->setFrom('nuloofairview@gmail.com', 'NU LOOF System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;
                
                $mail->send();
            } catch (Exception $e) {
                error_log("Email to $email failed: " . $mail->ErrorInfo);
            }
        }

        // Email content with table
        $emaillostBody = "<p>Good news! A possible match has been found for your item. Please visit the Discipline Office to verify it.</p>
        <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
            <tr>
                <th style='background-color: #f2f2f2;'>Attribute</th>
                <th style='background-color: #f2f2f2;'>Lost Item</th>
                <th style='background-color: #f2f2f2;'>Found Item</th>
            </tr>
            <tr><td>Item Name</td><td>$lostItemName</td><td>$foundItemName</td></tr>
            <tr><td>Brand</td><td>$lostBrand</td><td>$foundBrand</td></tr>
            <tr><td>Color</td><td>$lostColor</td><td>$foundColor</td></tr>
            <tr><td>Category</td><td>$lostCategory</td><td>$foundCategory</td></tr>
            <tr><td>Description</td><td>$lostDescription</td><td>$foundDescription</td></tr>
            <tr><td>Date Reported</td><td>$lostDate</td><td>$foundDate</td></tr>
        </table>
        <br>
        <p>Thank you.</p>
        <p><strong>NULooF - Lost and Found System</strong></p>";

        $emailfoundBody = "<p>Good news! An item you reported as found has a possible match</p>
        <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
            <tr>
                <th style='background-color: #f2f2f2;'>Attribute</th>
                <th style='background-color: #f2f2f2;'>Found Item</th>
                <th style='background-color: #f2f2f2;'>Lost Item</th>
            </tr>
            <tr><td>Item Name</td><td>$foundItemName</td><td>$lostItemName</td></tr>
            <tr><td>Brand</td><td>$foundBrand</td><td>$lostBrand</td></tr>
            <tr><td>Color</td><td>$foundColor</td><td>$lostColor</td></tr>
            <tr><td>Category</td><td>$foundCategory</td><td>$lostCategory</td></tr>
            <tr><td>Description</td><td>$foundDescription</td><td>$lostDescription</td></tr>
            <tr><td>Date Reported</td><td>$foundDate</td><td>$lostDate</td></tr>
        </table>
        <br>
        <p>Thank you.</p>
        <p><strong>NULooF - Lost and Found System</strong></p>";

        // Send email to both reporters
        sendNotification($lostEmail, 'Your lost item has a match!', $emaillostBody);
        sendNotification($foundEmail, 'Update: Possible match for found item', $emailfoundBody);

        echo json_encode(['success' => true, 'message' => 'Match successfully processed and emails sent.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $conn->close();
}
?>
