<?php
namespace Ability\Warehouse;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;




class EmailService{
  /*
    Send an email from no-reply@configuredDomain
  */
  public static function SendMail($to, $subject, $message){
/*
    try {
      //Server settings
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
      $mail->isSMTP();                                            // Send using SMTP
      $mail->Host       = 'email-smtp.us-east-1.amazonaws.com';                    // Set the SMTP server to send through
      $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
      $mail->Username   = ConfigurationManager::GetParameter("EmailFromUser") . "@" . ConfigurationManager::GetParameter("EmailFromDomain");

      $mail->Password   = 'secret';                               // SMTP password
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
      $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

      //Recipients
      $mail->setFrom('from@example.com', 'Mailer');
      $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
      $mail->addAddress('ellen@example.com');               // Name is optional
      $mail->addReplyTo('info@example.com', 'Information');
      $mail->addCC('cc@example.com');
      $mail->addBCC('bcc@example.com');

      // Attachments
      $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
      $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

      // Content
      $mail->isHTML(true);                                  // Set email format to HTML
      $mail->Subject = 'Here is the subject';
      $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
      $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

      $mail->send();
      echo 'Message has been sent';
    } catch (\Exception $e) {

    }*/

    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    $sender = ConfigurationManager::GetParameter("EmailFromUser") . "@" . ConfigurationManager::GetParameter("EmailFromDomain");

    $headers .= 'From: ';
    $headers .= "<$sender>" . "\r\n";

    Log::info("SENDING MAIL to: [$to] subject: [$subject] body: [$message]");

    mail($to,$subject,$message,$headers);

  }

  public static function GeneratePasswordResetMessage($url){

    $message = "
    <html lang='en'>
      <head>
        <meta charset='utf-8'>
        <title></title>
      </head>
      <body>
        A password reset has been requested.  It will expire in under five minutes.
        <br>Please ignore this request if this was not requested.<br><br>
        <a href=$url>CLICK HERE</a> to reset your password.
      </body>
    </html>";

    return $message;
  }
}
?>
