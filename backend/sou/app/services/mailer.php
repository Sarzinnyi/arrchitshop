<?php
namespace App\services;
use PHPMailer\PHPMailer\PHPMailer;
class mailer{
	public static function sentOtp($otp,$email){
		$mail=new PHPMailer(true);
$mail->isSMTP();
  $mail->Host = 'smtp-relay.brevo.com';
$mail->SMTPAuth = true;
$mail->Username = '9f19b0001@smtp-brevo.com';
$mail->Password = getenv("smt_password");

$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('sarzinnyi@gmail.com', 'Zinnyi');
$mail->addAddress('xintun357@gmail.com');
$mail->isHTML(false); 
$mail->Subject = 'Your Zinnyi Verification Code'; 
$mail->Body = "Your Zinnyi verification code is: $otp\n\n" . "This code will expire in 5 minutes.\n\n" . "If you did not request this code, you can safely ignore this email.\n\n" . "Thank you,\n" . "Zinnyi"; 
$mail->send(); 
	}
	
}
