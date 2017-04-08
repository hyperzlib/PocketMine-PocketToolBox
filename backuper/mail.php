<?php
function mailto($address, $url){
	date_default_timezone_set('Asia/Shanghai');
	require 'PHPMailer/PHPMailerAutoload.php';
	$html = file_get_contents('contents.html');
	$html = str_replace(['%url%', '%mail%'], [$url, $address], $html);
	//Create a new PHPMailer instance
	$mail = new PHPMailer;
	//Tell PHPMailer to use SMTP
	$mail->isSMTP();
	//Enable SMTP debugging
	// 0 = off (for production use)
	// 1 = client messages
	// 2 = client and server messages
	$mail->SMTPDebug = 0;
	//Ask for HTML-friendly debug output
	$mail->Debugoutput = 'html';
	//Set the hostname of the mail server
	$mail->Host = "smtp.126.com";
	//Set the SMTP port number - likely to be 25, 465 or 587
	$mail->Port = 25;
	//Whether to use SMTP authentication
	$mail->SMTPAuth = true;
	$mail->CharSet = 'UTF-8';
	//Username to use for SMTP authentication
	$mail->Username = "mcleague@126.com";
	//Password to use for SMTP authentication
	$mail->Password = "mcleague2333";
	//Set who the message is to be sent from
	$mail->setFrom('mcleague@126.com', 'MC技术联盟');
	//Set an alternative reply-to address
	$mail->addReplyTo('mcleague@126.com', 'MC技术联盟');
	//Set who the message is to be sent to
	$mail->addAddress('mcleague@126.com');
	//Set the subject line
	$mail->Subject = '服务器备份提醒';
	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	$mail->msgHTML($html, dirname(__FILE__));
	//Replace the plain text body with one created manually
	//$mail->AltBody = '';
	$mail->addAttachment('images/logo.png');
	return $mail->send();
}