<?php
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

function send_confirmation_email($email, $event_title) {
  $mail = new PHPMailer(true);
  
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@gmail.com';
    $mail->Password = 'your_app_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('noreply@events.com', 'Event System');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Registration Confirmation';
    $mail->Body = "You have successfully registered for: <b>$event_title</b>";
    
    $mail->send();
  } catch (Exception $e) {
    error_log("Email error: {$mail->ErrorInfo}");
  }
}