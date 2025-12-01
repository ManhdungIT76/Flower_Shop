<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$topic   = trim($_POST['topic'] ?? '');
$message = trim($_POST['message'] ?? '');

try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nguyenduythinh1112@gmail.com';
    $mail->Password   = 'vvdzfsuzpbkkrfbq'; // app password không dấu cách
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('nguyenduythinh1112@gmail.com', 'Blossomy Contact');
    $mail->addAddress('nguyenduythinh1112@gmail.com');
    $mail->addReplyTo($email ?: 'no-reply@example.com', $name ?: 'Khách');

    $mail->isHTML(true);
    $mail->Subject = "Liên hệ mới: {$topic}";
    $mail->Body    = "
        <strong>Họ tên:</strong> {$name}<br>
        <strong>Email:</strong> {$email}<br>
        <strong>Điện thoại:</strong> {$phone}<br>
        <strong>Chủ đề:</strong> {$topic}<br>
        <strong>Tin nhắn:</strong><br>
        <pre style='font-family:inherit'>{$message}</pre>
    ";
    $mail->AltBody = "Ho ten: {$name}\nEmail: {$email}\nDien thoai: {$phone}\nChu de: {$topic}\nTin nhan:\n{$message}";

    $mail->send();
    header('Location: contact.php?sent=1');
    exit;
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
    header('Location: contact.php?sent=0');
    exit;
}
