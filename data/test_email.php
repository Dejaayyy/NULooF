<!-- <?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nuloofairview@gmail.com'; // Replace with your email
    $mail->Password = 'hjue xwjp hutm zmyg'; // Use an App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('nuloofairview@gmail.com', 'NU LooF System');
    $mail->addAddress('email'); // Replace with the recipient's email

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<h1>Hello, this is a test email from PHPMailer!</h1>';

    $mail->send();
    echo 'Email has been sent successfully!';
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?> -->
