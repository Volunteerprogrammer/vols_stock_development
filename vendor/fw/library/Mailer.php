<?php
namespace lib;

class Mailer {
	protected smtphost = 'smtp.example.com';
	protected port = 587;
	protected username = 'user@example.com';
	protected password = 'secret';
	protected fromaddress = 'from@example.com';
	protected replytoaddress = 'info@example.com';
	protected replytoalias = 'Information'; 
	public function __construct(protected \PHPMailer\PHPMailer mail,
								protected \PHPMailer\Exception mailexception,
								protected \PHPMailer\SMTP smtp,
								)  {
	}
	public function init($mailparams){
		$this->smtphost = $mailparams["smtphost"];
		$this->port = $mailparams["port"];
		$this->username = $mailparams["username"];
		$this->password = $mailparams["password"];
		$this->fromaddress = $mailparams["fromaddress"];
		$this->replytoaddress = $mailparams["replytoaddress"];
		$this->replytoalias = $mailparams["replytoalias"];
	}
	public function putsmtphost($s){
		$this->smtphost = $s;
	}
	public function putport($s){
		$this->port = $s;
	}
	public function putusername($s){
		$this->username = $s;
	}
	public function putpassword($s){
		$this->password = $s;
	}
	public function putfromaddress($s){
		$this->fromaddress = $s;
	}
	public function putreplyto($s,$t){
		$this->replytoaddress = $s;
		$this->replytoalias = $t;
	}
	public function sendmail($recipients,$cc,$bcc,$attachments,$subject){
		try {
		    //Server settings
		    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
		    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
		    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
		    $mail->isSMTP();

		    $mail->Host       = $this->smtphost;
		    $mail->Port       = $this->port; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
		    $mail->Username   = $this->username;                     //SMTP username
		    $mail->Password   = $this->password;                               //SMTP password
		    //Recipients
		    $mail->setFrom($this->fromaddress, 'Mailer');
		    $mail->addReplyTo($this->replytoaddress,$this->replytoalias);
		    foreach ($recipients as $alias => $recipient) {
		    	$mail->addAddress($recipient, $alias);     
		    }
		    foreach ($cc as $alias => $recipient) {
		    	$mail->addCC($recipient, $alias);     
		    }
		    foreach ($bcc as $alias => $recipient) {
		    	$mail->addBCC($recipient, $alias);     
		    }
		    $mail->addBCC($this->replytoaddress);     
		    foreach ($attachments as $attachment) {
		    	$mail->addAttachment($attachment);     
		    }
		    //Content
		    $mail->isHTML(true);                                  //Set email format to HTML
		    $mail->Subject = $subject;
		    $mail->Body    = $htmlmgs;
		    $mail->AltBody = $textmsg;
		    $mail->send();
		    echo 'Message has been sent';
		} catch (\Exception $e) {
		    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

	}

}