<?php
namespace app\controller;
use \lib\StdLib as lib;
class UserPageController extends ViewController{
    private $trace=false;
	public  function __construct(protected \app\view\head\HTMLHead $headsection,
                                 protected \app\view\body\BodyCollection $bodies,
                                 protected \app\view\form\FormCollection $forms,
                            ){
        if ($this->trace ) { echo gtab(0)."Enter ".__METHOD__."<br>\n"; }
	 }
    public  function init($session,$managercollection,$errorhandler) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        parent::init($session,$managercollection,$errorhandler)
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n";}
     }
    // ========================================================================================
    private function setpages(&$errormessage, $trace=false) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__." target = ".$this->pagenum."<br>\n"; }
        $this->setthispage(0,$this->pagenum,$this->usermanager,$this->forms->UserForm(),$errormessage,"given_name");
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
     }    
    private function prepareHTMLbody (&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__.$this->pagenum."<br>"; }
        $user_id = $this->session->getuserid();
        try {
            $data = "";
            $parents = $userroles = $roles = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,"given_name",$parents,$numrows,false,false);
            $success = $success && $this->manager->getalluserroles($userroles,$numrows,"user_id",false);
            $success = $success && $this->manager->getallroles($roles,$numrows,"name",false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$roles,$userroles,$this->pagenum);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get user '{$user_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
}
