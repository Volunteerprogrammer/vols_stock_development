<?php
namespace app\view\form;
use \lib\StdLib as lib;
class PageForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 30;
    protected $hintwidth = 40;
    protected $fields = [];
    protected $formname = "pageform";
    protected $objname = "Page";
    protected $parentname = "";
    protected $parentobj = "";
    protected $pagenum;
    protected $pageid;
    protected $names;
    protected $parents;
    protected $actions;
    protected $pageactions;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$pages=[],$parents="",$trace=false,$actions=[],$pageactions=[],$pagenum='' ) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($pageactions)) {  // add the action_ids for the page's actions in a new element in each $page array
            $this->addlinkstodata($pages,$actions,$pageactions,$trace);
         }
        parent::init($session,$pages,$parents,$trace);
        $this->actions = $actions;
        $this->pageactions = $pageactions;
        $this->pagenum = $pagenum;
        $this->pageid = $this->requestdata["id"]??"";
     }
    protected function addtonames($page){
        $this->names[$page["id"]] = $page["pagenumber"]." ".ucfirst(strtolower($page["name"]));
     }                
    public function addlinkstodata(&$pages=[],$actions=[],$pageactions=[],$trace=false) {
        foreach ($pages as &$page) {
            foreach ($actions as $action) {
                $pa=false;
                foreach ($pageactions as $pageaction) {
                    if (($page["id"] === $pageaction["page_id"]) && ($action["id"] === $pageaction["action_id"]) ) {
                        $pa = true;
                        break;
                    }
                }
                $page["action".$action["id"]] = $pa;
            }
        }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(
            "id"=>"",
            "pagenumber"=>"",
            "name" => "",
            "pagetype" => "",
            "unrestricted" => "",
                            );
     }
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $formfields = $this->component->buildinputrow("pagenumber",1,"",'PageNumber','PageNumber',10,10,true,'',''); 
        $formfields .= $this->component->buildinputrow("name",2,"",'Name','name',20,64,true,'','Descriptive text only.'); 
        $formfields .= $this->component->rendersectionheading("General",inputgroup:"generalgroup");
        // $formfields .= $this->component->buildcheckboxrow("pagetype","1","",false,4,'Not shown in Menus','Used for system launched pages.',false,false,false);
        // $buttons should be an array of button arrays e.g. [["SMS"=>1],["Email"=>2],["Phone"=>3]];
        $formfields .='  <input type="hidden" name="pagetype" data-fnum="3" id="pagetype"  value="" />'."\n";
        $buttonarray = [["Other"=>0],["System"=>1],["Roster"=>2],["Editor"=>3],["Reports"=>4]];
        $buttons = $this->component->renderradiobuttons('pt',$buttonarray,0,'',3,true,'pt',false);
        $formfields .= $this->component->renderformrow('pagetyperow','','Page type',0,'','','pagetype',$buttons);

        $formfields .= $this->component->buildcheckboxrow("unrestricted","1","",false,4,'Unrestricted access','No permissions required to access this page.',false,false,false);

        $fn = 5;
        $buttons = ["rightid"=>"showrowsbtn","righttext"=>"Show linked","rightscript"=>"","leftid"=>"","lefttext"=>"","leftscript"=>""];
        $heading = "<span id='statustextspan'>ALL</span> Actions (allowed on this page)";
        $formfields .= $this->component->rendersectionheading($heading,buttons:$buttons);
        $this->component->setwidths (30,65,5);
        $hiddencheckboxes = '';
        foreach ($this->actions as $id=>$action) {
            $actionname = "link_action".$action["id"];
            $rowdata = [];
            $rowdata["pagetype"]= $action["page_type"];
            $formfields .= $this->component->buildcheckboxrow($actionname,$action["id"],"",true,$fn++,$action["name"], rowdata:$rowdata);
            $hiddencheckboxes .= '<input type="hidden" name="'.$actionname.'"  value=false />';
         }
        $this->preparecommontop(hiddeninputs:$hiddencheckboxes, selecttext:$this->pageid); // named argument
        return $formfields;
     }
    public function formscript() {
        // passive validation of email and phone number when being entered
          //    Thus the SUBCLASS should do the following:
          //        1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
          //        2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific action handlers, initialise form-specific (third party) components
          //        3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
          //        4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
          //        5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
          //        6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
          //        7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
          //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
        $postloadfieldsscript = <<<JS
           // find the pagetype radio button to check based on the data
            const pt = jQuery("#pagetype").val();  // loaded from the hidden fields
            const radioid = "#pt"+ pt; // this is the button to be checked
            jQuery(radioid).prop("checked", true).trigger("click");

            if (jQuery("#showrowsbtn").text() === "Show linked") {
                jQuery("#dataspace [id^=link_action]").removeClass("hidden");
            } else {
                jQuery("#dataspace [id^=link_action].vols-tablerow").addClass("hidden");
                jQuery("#dataspace [id^=link_action] input[type='checkbox']:checked").parent().parent().removeClass("hidden");        
            }
            showhidepages();

         JS;
        $postclearfieldsscript = <<<JS

                    jQuery("input[type='checkbox']").prop("checked",false);
                    $('input:radio').each(function () { $(this).prop('checked', false); });
                    jQuery("#pagetype").val('');
                    showhidepages();
        JS;
        $presavescript = <<<JS

                    jQuery("#formerror").html("") ;
                    // now recover the index from the 'checked' radio to 
                    // determine the appropriate value for 'message_by'
                    jQuery("#pagetype").val(jQuery("input[type=radio][name='pt']:checked").val());                    
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
                                    $presavescript
                                    );         
        $script .= <<<JS
            function showhidepages() {
                setchildselectorheadingtext("","","pagetype",["0",jQuery("#pagetype").val()]);
                const isroster = jQuery("#pagetype").val() === '2';
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            jQuery(document).on('click', "input[name='pt']", function() {
                jQuery("#pagetype").val(jQuery(this).val());
                showhidepages();
            });
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#pagenumber").val()){ 
                    jQuery("#pagenumberrow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
                // only required if the form property 'idselection' = false 
            }
            function getchildnames() { return ["action","Actions allowed on this page."]}

        JS;
        return $script;
     }
}
