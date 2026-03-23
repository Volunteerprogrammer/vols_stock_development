<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ConfigForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $fields = [];
    protected $formname = "configform";
    protected $objname = "Configuration";
    protected $parentname = "";
    protected $parentobj = "";
    protected $pagenum;
    protected $names;
    protected $data;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = true;
        $this->actionbuttons = ["reset"=>1,"save"=>1];
    }
    public function init($session,$data=[],$parents=[],$trace=false ) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->user_id = $this->session->getuserid();
        $this->pagenum = $this->session->getpagenum();
        $this->errorhandler = $this->session->geterrorhandler();
        $this->processed =  $this->session->getprevpagenum() == $this->pagenum;
        $this->isadmin = $this->session->isadmin();
        $this->menumanager = $this->session->getmenumanager();
        $this->data = $data;
        $this->component->init($this->session,$this->processed,$this->promptwidth,$this->inputwidth,$this->hintwidth,$this->recorddelimiter,$this->isadmin,$this->singlerecord);
        $this->ids = "1".$this->recorddelimiter;
// lib::v(__METHOD__,$this->data);
        $this->fields["id"] = 1;
        foreach ($this->data as $field) {
            $this->fields[$field["name"]] = "";
            $this->hiddenfields .= $field["value"].$this->fielddelimiter ;
        }
        $this->hiddenfields .= $this->recorddelimiter;
        $this->names = [];
        $this->preparecommontop(false,true,'','');
     }

    protected function buildhiddendatafields($trace = false) {}     
    public function initfields(){}
    protected function addtonames($row) {return true;} 

    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $formfields = "";
        $f = 1;
        $group = "";
        $lastgroup = "xxx";
        foreach ($this->data as $field) {
            if ($field["name"] !== "id") {
                if ($field["group"] !== $lastgroup) {
                    $formfields .= $this->component->rendersectionheading($field["group"],'','','','','','','','',true);
                    $lastgroup = $field["group"];
                }
                $cols = trim($field["comment"]) == "" ? 50 : 30;
                $oflowshow = trim($field["comment"]) == "" ? " vols-overflow-show" : "";

                $formfields .= $this->component->buildtextarearow($field["name"],$f++,$field["value"],$field["name"],'',$cols,1,4096,true,'',$field["comment"],"","",$oflowshow);
            }
        }
       return $formfields;
     }
    public function formscript() {
        // passive validation of email and phone number when being entered
        //    Thus the SUBCLASS should do the following:
        //        1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
        //        2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific page handlers, initialise form-specific (third party) components
        //        3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
        //        4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
        //        5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
        //        6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
        //        7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
        //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
        $postloadfieldsscript = <<<SCRIPT
        SCRIPT;
        $postclearfieldsscript = <<<SCRIPT
                    jQuery("input[type='checkbox']").prop("checked",false);
                    $('input:radio').each(function () { $(this).prop('checked', false); });
        SCRIPT;
        $presavescript = <<<SCRIPT
                    jQuery("#formerror").html("") ;
        SCRIPT;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //$postupdatescript=
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript
                                    );
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            function formhaserrors() {
                return false;
            }
            function displayselectedrecord() {
                // only required if the form property 'idselection' = false 
            }
        JS;
        return $script;
     }
}
