<?php
include('db.php');
require '../vendor/autoload.php'; // Ensure PHPMailer is installed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lostReportId = $_POST['lost_report_id'];
    $foundReportId = $_POST['found_report_id'];

    error_log("Attempting to re-match lost report ID $lostReportId with found report ID $foundReportId");

    // Start transaction
    $conn->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);

    try {
        // Fetch lost item reporter email
        $queryLost = "SELECT 
                    r.ITEM_NAME, 
                    r.ITEM_BRAND, 
                    r.ITEM_COLOR, 
                    r.ITEM_CATEGORY, 
                    r.ITEM_DESCRIPTION, 
                    r.ITEM_DATE,
                    COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                    FROM reports_table r
                    LEFT JOIN user_info u ON r.email_add = u.email_add
                    WHERE r.report_id = ?";
        $stmtLost = $conn->prepare($queryLost);
        $stmtLost->bind_param("i", $lostReportId);
        $stmtLost->execute();
        $stmtLost->bind_result($lostItemName, $lostBrand, $lostColor, $lostCategory, $lostDescription, $lostDate, $lostEmail);
        $stmtLost->fetch();
        $stmtLost->close();

        // Fetch found item reporter email
        $queryFound = "SELECT 
                    r.ITEM_NAME,
                    r.ITEM_BRAND,
                    r.ITEM_COLOR,
                    r.ITEM_CATEGORY,
                    r.ITEM_DESCRIPTION,
                    r.ITEM_DATE,
                    COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                    FROM reports_table r
                    LEFT JOIN user_info u ON r.email_add = u.email_add
                    WHERE r.report_id = ?";
        $stmtFound = $conn->prepare($queryFound);
        $stmtFound->bind_param("i", $foundReportId);
        $stmtFound->execute();
        $stmtFound->bind_result($foundItemName, $foundBrand, $foundColor, $foundCategory, $foundDescription, $foundDate, $foundEmail);
        $stmtFound->fetch();
        $stmtFound->close();

        // Update lost item
        $queryUpdateLost = "UPDATE reports_table
                            SET match_status = 'not_found', matched_with = NULL, HOLDING_STATUS = 'Not yet retrieved'
                            WHERE report_id = ?";
        $stmtUpdateLost = $conn->prepare($queryUpdateLost);
        $stmtUpdateLost->bind_param("i", $lostReportId);
        $stmtUpdateLost->execute();
        $stmtUpdateLost->close();

        // Update found item
        $queryUpdateFound = "UPDATE reports_table
                             SET match_status = 'not_found', matched_with = NULL
                             WHERE report_id = ?";
        $stmtUpdateFound = $conn->prepare($queryUpdateFound);
        $stmtUpdateFound->bind_param("i", $foundReportId);
        $stmtUpdateFound->execute();
        $stmtUpdateFound->close();

        // Commit transaction
        $conn->commit();

        // Send email notifications
        function sendNotification($email, $subject, $body) {
            if (!$email) {
                error_log("Skipping email: No recipient email found.");
                return;
            }

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nuloofairview@gmail.com';
                $mail->Password = 'hjue xwjp hutm zmyg'; // Consider using environment variables for security
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->setFrom('nuloofairview@gmail.com', 'NU LOOF System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;

                if (!$mail->send()) {
                    error_log("Email to $email failed: " . $mail->ErrorInfo);
                } else {
                    error_log("Email successfully sent to $email.");
                }
            } catch (Exception $e) {
                error_log("Exception while sending email to $email: " . $e->getMessage());
            }
        }

        // Email for lost item reporter
        sendNotification(
            $lostEmail,
            'Your lost item needs to be re-matched',
            "<p>Good day! We are sorry to inform you that your item needs to be re-matched to find a better possible match.</p>
            <p>We will soon reach out for a possible match for your lost item. Kindly wait for updates regarding your lost item.</p>
            <br>
            <p><strong>Lost Item Details:</strong></p>
            <p><strong>Item Name:</strong> $lostItemName</p>
            <p><strong>Brand:</strong> $lostBrand</p>
            <p><strong>Color:</strong> $lostColor</p>
            <p><strong>Category:</strong> $lostCategory</p>
            <p><strong>Description:</strong> $lostDescription</p>
            <p><strong>Date Lost:</strong> $lostDate</p>
            <br>
            <p>Thank you.</p>
            <p><strong>NULooF - Lost and Found System</strong></p>"
        );

        // Email for found item reporter
        sendNotification(
            $foundEmail,
            'Update: Possible match needs to be re-matched',
            "<p>Good day! We would like to inform you that the item you reported as found needs to be re-matched.</p>
            <p>Kindly wait for more updates regarding your found item.</p>
            <p><strong>Found Item Details:</strong></p>
            <p><strong>Item Name:</strong> $foundItemName</p>
            <p><strong>Brand:</strong> $foundBrand</p>
            <p><strong>Color:</strong> $foundColor</p>
            <p><strong>Category:</strong> $foundCategory</p>
            <p><strong>Description:</strong> $foundDescription</p>
            <p><strong>Date Found:</strong> $foundDate</p>
            <br>
            <p>Thank you.</p>
            <p><strong>NULooF - Lost and Found System</strong></p>"
        );

        echo json_encode(['success' => true]);

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $conn->close();
}
?>
