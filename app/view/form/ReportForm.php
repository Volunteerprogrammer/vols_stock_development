<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 10;
    protected $inputwidth = 85;
    protected $hintwidth = 5;
    protected $fields = [];
    protected $formname = "reportform";
    protected $objname = "Report";
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
        $this->fields["id"] = "";
     }
    protected function addtonames($report){
            $this->names[$report["id"]] = $report["name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $buttons=[["id"=>"csv","text"=>"Print CSV", "buttonclass"=>"doitbg", "cellclass"=>"vols-width-40"],["id"=>"pdf","text"=>"Print PDF", "buttonclass"=>"doitbg", "cellclass"=>"vols-width-40"]];
        $formfields = $this->component->buildbuttonsrow($buttons,"reportsbuttons"); 
        $formfields .= $this->component->buildinputrow("name",1,"",'Name','Name',20,64,true,'',''); 
        $formfields .= $this->component->buildtextarearow("query",2,"",'Query','Query',75,20,16384,false); 
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
        $postloadfieldsscript = <<<JS
                    jQuery("#query").val(atob(jQuery("#query").val()))
        JS;
        $postclearfieldsscript = <<<JS
        JS;
        $presavescript = <<<JS
                    jQuery("#formerror").html("") ;
                    const a = btoa(jQuery("#query").val());
                    jQuery("#query").val(a);
        JS;
        $onloadscript = <<<JS
                    jQuery("#csv").on("click",async function() {
                        vols.cursor.wait();
                        result = await  doServerRequest(0,jQuery("#query").val(),'generatecsvreport');
                        downloadTextFile(result,"report.csv")
                        clearwaitcursor();
                    })
        JS;


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
                                    $presavescript,
                                    "",
                                    $onloadscript
                                    );
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
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
