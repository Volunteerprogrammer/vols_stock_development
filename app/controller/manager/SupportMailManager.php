<?php
namespace app\controller\manager;
use \Mailjet\Resources;
use \lib\StdLib as lib;
// require '\Mailjet\vendor\autoload.php';
class SupportMailManager extends \fw\controller\manager\MailJetManager{
	protected $trace = FALSE;
	protected $config;
	protected $session;
	protected $db;
	public function init($session,$trace=false) {
		$this->session = $session;
		parent::init($this->session,$trace);
	 }
	public function sendsupportemail($message,$subject,&$responsestr,$errorhandler,$trace=false){
		// $email=["recipients"=>[["Email"=>"a@b.c","name"="abc"],[],[]],
		// 		"ccs"=>[["Email"=>"a@b.c","name"="abc"],[],[]],
		// 		"bcc"=>[["Email"=>"a@b.c","name"="abc"],[],[]],
		// 		"attachments"=>[["Filename"=>"","Content-type"=>"abc","Content"=>"base64 content"],[],[]],
		// 		"Subject" => $email["subject"],
		// 		'TextPart' => $email["text"],
		// 		'HTMLPart' => $email["html"],
		// 		'mailjet-CustomID' => $email["mailjet-CustomID"]
		// 	]
    	try {
	        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
			$config = $this->session->getconfig();
			$to_email["Email"] = $config["app"]["ERR_EMAIL_ADDR"];
			$to_email["Name"] = "Support";
			$email["To"][] = $to_email;
			$email["Subject"]=$subject;
			$email["TextPart"]=$message;
        	if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
	       	return $this->sendemail($email,null,$responsestr,$email_id,$trace);
		} catch (\Exception $e) {
		    return "Message could not be sent. Exception =  {$e->getMessage()} ";
		}
	 }

}
