<?php
namespace app\controller\manager;
use \Mailjet\Resources;
use \lib\StdLib as lib;
// require '\Mailjet\vendor\autoload.php';
class EMailManager extends \fw\controller\manager\MailJetManager{
	private $trace = FALSE;
    public function __construct(protected \apptable\EmailTable $emailtable,
                                protected \apptable\EmailUserTable $emailusertable,
                                protected \apptable\EmailSessionTable $emailsessiontable) {
    	parent::__construct($emailtable,$emailusertable);
     }
 	public function init($session,$trace=false) {
		parent::init($session,$trace);
        $this->emailsessiontable->init($this->db);
	 }
    public function getlogo($index){
        $imgsrc = $this->config["app"]["EMAILLOGO{$index}"];
        $imgstyle = $this->config["app"]["EMAILLOGOSTYLE{$index}"];
        $imgattributes = $this->config["app"]["EMAILLOGOATTRIBUTES{$index}"];
        $logo = "<img src='{$imgsrc}' alt='Logo' style='$imgstyle}' {$imgattributes}>";
        return $logo;
     }
	public function getheading(){
		$logo1 = $this->getlogo(1);
		$logo2 = $this->getlogo(2);
		$html = <<<HTML
			<div style="display:flex;flex-flow: row nowrap;align-items:center;justify-content:center;height:70px;width:100%">
				{$logo1}
				<div style='flex-flow:column nowrap;justify-content:center;font-weight:700;margin: 0 15px;padding: 0 10px'> 
					<div style='width:100%;text-align:center'>Greetings from the</div>
					<div style='width:100%;text-align:center'>{$this->config["app"]["ORGANISATIONNAME"]}</div>
					<div style='width:100%;text-align:center'>Food Bank</div>
				</div>
				{$logo2}
			</div>
			<div style="display:flex;flex-flow: row nowrap;align-items:center;justify-content:center;width:100%">
				<div style="max-width:400px">   
		HTML;
		$text = "Greetings from the {$this->config["app"]["ORGANISATIONNAME"]} {$this->config["app"]["DEPARTMENT"]}";
		return ["html"=>$html, "text"=>$text];;
	 }
	public function getfooter(){
		if (array_key_exists("EMAILSIGNATURE1", $this->config["app"])) {
			$sig1 =  $this->config["app"]["EMAILSIGNATURE1"];
		}
		if (array_key_exists("EMAILSIGNATURE2", $this->config["app"])) {
			$sig2 =  $this->config["app"]["EMAILSIGNATURE2"];
		}
		$html = <<<HTML
					<p>As always, we gratefully appreciate your contribution.</p>
					<p>Kind regards,</p>
					<p><strong>{$sig1}<br />{$sig2}</strong></p>
				</div>
			</div>
		HTML;
        $text  = "As always, we gratefully appreciate your contribution.\n\n";
        $text .= "Kind regards\n\n";
        $text .= "{$sig1}\n{$sig2}";
		return ["html"=>$html, "text"=>$text];
     }
	public function sendmail($email,$user_id,$volunteersession_id,&$responsestr,$errorhandler=null,$trace=false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
    	try {
	       	$success =  $this->sendemail($email,$user_id,$responsestr,$email_id,$errorhandler,$trace);
	       	if ($success) {
			    if ($volunteersession_id && $email_id) {
			    	// email and emailuser have been saved. Now save connection to session
		            $this->emailsessiontable->clear();
		            $this->emailsessiontable->setfieldvalue("email_id",$email_id);
		            $this->emailsessiontable->setfieldvalue("session_id",$volunteersession_id);
		            $savesuccess = $this->emailsessiontable->insert(false,$esid,$trace);
		       	}
	       	}
			return $success; 
		} catch (\Exception $e) {
		    return "Message could not be sent. Mailer Error: {$e->getMessage()}";
		}
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
	}

}
