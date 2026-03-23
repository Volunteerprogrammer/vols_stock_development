<?php
namespace fw\controller\manager;
use \lib\StdLib as lib;
use \Mailjet\Resources;
// require '\Mailjet\vendor\autoload.php';
class MailJetManager {
	private $trace = FALSE;
	protected $publickey;
	protected $privatekey;
	protected $fromaddress ;
	protected $fromname ;
	protected $replytoaddress ;
	protected $replytoname ;
	protected $bcc ;
	protected $mailjet;
	protected $config;
	protected $db;
	protected $session;
    public function __construct( protected \apptable\EmailTable $emailtable,
                                protected \apptable\EmailUserTable $emailusertable) {
     }
	public function init($session) {
		$this->session = $session;
        $this->db = $this->session->getdb();
        $this->emailtable->init($this->db);
        $this->emailusertable->init($this->db);
		$this->config = $this->session->getconfig();
		$this->publickey = $this->config["app"]["EMAILPUBLICKEY"] ;
		$this->privatekey = $this->config["app"]["EMAILPRIVATEKEY"] ;
		$this->fromaddress = $this->config["app"]["FROMADDRESS"] ;
		$this->fromname = isset( $this->config["app"]['FROMNAME'])?$this->config["app"]["FROMNAME"] :""; 
		$this->replytoaddress = isset( $this->config["app"]['REPLYTOADDRESS'])?$this->config["app"]["REPLYTOADDRESS"]:""; 
		$this->replytoname = isset( $this->config["app"]['REPLYTONAME'])?$this->config["app"]["REPLYTONAME"]:""; 
		$this->bcc = isset($this->config["app"]['BCCADDRESS'])?$this->config["app"]['BCCADDRESS']:""; 
		try {
			 $this->mailjet = new \Mailjet\Client($this->publickey,$this->privatekey,true,['version' => 'v3.1']); 
		} catch (\Exception $e) {
		    echo "Message could create mailjet client. Mailer Error: {$mail->ErrorInfo}";
		}
	 }
	public function sendemail($email,$user_id,&$responsestr,&$email_id,$errorhandler,$trace=false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
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
			if (!isset($email["From"]) && ($this->fromaddress !== "")) {
				$email["From"] = ["Email"=>$this->fromaddress,"Name"=>$this->fromname];
			}
			if (!isset($email["Bcc"]) && ($this->bcc !== "")) {
				$email["Bcc"] = [["Email"=>$this->bcc]];
			}
			$body = ['Messages' => [$email]];
			$response = $this->mailjet->post(Resources::$Email, ['body' => $body]);
			$responsestr = $response->getStatus().": ".$response->getReasonPhrase();
			$this->saveemail ($body,$response,$email_id,$user_id,$errorhandler,$trace);
			return $response->success(); 
		} catch (\Exception $e) {
			$message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo} ".PHP_EOL.$e->getMessage();
			$errorhandler->emailerror($message);
		    return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	 }
	private function saveemail ($body,$response,&$email_id,$user_id,$errorhandler,$trace=false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
		// ================== RESPONSE ====================
		// The get, post, put and delete method will return a Response object with the following available methods:
		// success() : returns a boolean indicating if the API call was successful
		// getStatus() : http status code (ie: 200,400 ...)
		// getData() : content of the property data of the JSON response payload if exist or 
		//              the full JSON payload returned by the API call. This will be PHP associative array.
		// getCount() : number of elements returned in the response
		// getReasonPhrase() : http response message phrases ("OK", "Bad Request" ...)

		// ,$response["success"],$response["body"]["Messages"]["0"]["status"],$response["rawResponse"]["reasonPhrase"]);
		// lib::v();
 		try {
	 	    if ($response->success()) {
	        	$response_str = $response->success()."//".$response->getStatus()."//".$response->getReasonPhrase();
	        } else {
				$response_str = lib::vob($response); // this returns a var_dump of $parameter in a string (using the output buffer)
				$response_str = substr($response_str,0,strpos($response_str,'["guzzleClient"')); // cut off from ["guzzleClient"...
	        }
			// lib::pr($body);
	        $jsonemail = json_encode($body["Messages"][0]);
	        $this->emailtable->setfieldvalue("status",$response->getReasonPhrase());
	        $this->emailtable->setfieldvalue("senddate",date("Y-m-d H:i:s"));
	        $this->emailtable->setfieldvalue("email",$jsonemail);
	        $this->emailtable->setfieldvalue("response",$response_str);
	        $success = $this->emailtable->insert(true,$email_id, $trace=false);
	        if ($success && isset($user_id)) {
	            $this->emailusertable->clear();
	            $this->emailusertable->setfieldvalue("email_id",$email_id);
	            $this->emailusertable->setfieldvalue("user_id",$user_id);
	        	$success = $this->emailusertable->insert(false,$eu, $trace=false);
	        }
	    } catch(\Exception $e) {
			$message = __METHOD__."  Error saving email log: ".$e->getMessage();
	    	$errorhandler->applicationerror($message);
	    }
        return $success;
     }
}