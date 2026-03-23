<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ActionForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 35;
    protected $hintwidth = 35;
    protected $fields = [];
    protected $formname = "actionform";
    protected $objname = "Action";
    protected $parentname = "";
    protected $parentobj = "";
    protected $pagenum;
    protected $actionid;
    protected $names;
    protected $parents;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
    }
    
    public function init($session,$data=[],$parents="",$trace=false,$x="",$xx="",$pagenum='' ) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session,$data,$parents,$trace);
        $this->pagenum = $pagenum;
        $this->actionid = $this->requestdata["id"]??"";
   }
    public function initfields(){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>"",
                                "name"=>"",
                                "code"=>"",
                                "page_type"=>""
                            );
    }
    protected function addtonames($action){
            $this->names[$action["id"]] = $action["name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $formfields = $this->component->buildinputrow("name",1,"",'Name','Name',20,64,true,'',''); 
        $formfields .= $this->component->buildinputrow("code",2,"",'Code','Code',20,64,true,'',''); 
        $formfields .='  <input type="hidden" name="page_type" data-fnum="3" id="page_type"  value="" />'."\n";
        $buttonarray = [["Unspecified"=>0],["System"=>1],["Roster"=>2],["Editor"=>3],["Reports"=>4]];
        $buttons = $this->component->renderradiobuttons('pt',$buttonarray,0,'',4,true,'pt',false);    
        $formfields .= $this->component->renderformrow('pagetyperow','','Page type',0,'','','',$buttons);
        $this->preparecommontop(false,false,'',$this->actionid);
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

            const pt = jQuery("#page_type").val();  // loaded from the hidden fields
            const radioid = "#pt"+ pt; // this is the button to be checked
            jQuery(radioid).prop("checked", true).trigger("click");
        SCRIPT;
        $postclearfieldsscript = <<<SCRIPT
                    jQuery("input[type='checkbox']").prop("checked",false);
                    $('input:radio').each(function () { $(this).prop('checked', false); });
        SCRIPT;
        $presavescript = <<<SCRIPT
                    jQuery("#page_type").val(jQuery("input[type=radio][name='pt']:checked").val());                    
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

            function loaddataintoform (recordnum) {
                const recdata = getdata();
                jQuery("#hiddenid").val(recdata[0]);
                jQuery("#name").val(recdata[1]);
                jQuery("#code").val(recdata[2]);
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#code").val()){ 
                    jQuery("#coderow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
                // only required if the form property 'idselection' = false 
            }
        JS;
        return $script;
     }
}
