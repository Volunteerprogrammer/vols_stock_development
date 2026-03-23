<?php
namespace app\view\form;
use \lib\StdLib as lib;
class UserProfileForm extends UserForm {
    protected $trace= false;                
    protected $names;                
    protected $parents;                
    protected $usermgr;                
    protected $rights;                
    protected $roles;                
    protected $data;                
    protected $userroles;                
    protected $formname = "userprofileform";
    protected $objname = "Volunteer";
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = true;
        $this->actionbuttons = ["reset"=>1,"save"=>1];
        $this->promptwidth = 20;
        $this->inputwidth = 45;
        $this->hintwidth = 35;

     }
    public function init($session,$data=[],$parents="",$trace=false,$r=[],$ur=[],$pagenum=''){
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->data[0] = $data;
        parent::init($session,$this->data,$parents,$trace,$r,$ur,$pagenum);
        $this->pagenum = $pagenum;
     }
    public function initfields($trace=false) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>$this->data[0]["id"]
                                ,"given_name"=>$this->data[0]["given_name"]
                                ,"family_name"=>$this->data[0]["family_name"]
                                ,"display_name"=>$this->data[0]["display_name"]
                                ,"email"=>$this->data[0]["email"]
                                ,"mobile"=>$this->data[0]["mobile"]
                            );
     }
    protected function addtonames($user){
        // this is in the subclass because the name field will vary by table
        $this->names[$user["id"]] = $user["given_name"]." ".$user["family_name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $form = parent::buildinputs($trace=false,true);
        return $form;
     }
    public function formscript() {
        $script = parent::formscript($trace=false);
        return $script;

     }
}
