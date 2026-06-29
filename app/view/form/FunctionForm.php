<?php
namespace app\view\form;
use \lib\StdLib as lib;
class FunctionForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $fields = [];
    protected $formname = "functionform";
    protected $objname = "Event";
    protected $parentname = "Page";
    protected $parentobj = "page_id";
    protected $pagenum;
    protected $names;
    protected $parents;
    protected $roles;
    protected $rolerows;
    protected $eventroles;
    protected $loaddowfieldscript;
    protected $loaddowvariablescript;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init( $session,$events=[],$parents="",$trace=false,$roles='',$sessionroles='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($sessionroles)) {  // add the sessions, session_roles and roles data to the data for each event
            $this->addlinkstodata($events,$sessions,$roles,$sessionroles,$trace);
        }
        parent::init($session,$events,$parents,$trace);
        $this->roles =$roles;
        $this->eventroles = $eventroles;
        if ($this->trace||$trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function addlinkstodata(&$events=[],$sessions=[],$roles=[],$sessionroles=[],$bookings=[],$trace=false) {
        foreach ($events as &$event) {
            foreach ($sessions as $session) {
                if ($session["event_id"] == $event["id"]) {
                    $event["session".$session["id"]."id"] = 1;
                    $event["session".$session["id"]."desc"] = $session["description"];
                    $event["session".$session["id"]."start"] = $session["start"];
                    $event["session".$session["id"]."finish"] = $session["finish"];
                    $break = 0;
                    foreach ($sessionroles as $sessionrole) {
                        if ($sessionrole["session_id"] == $session["id"]) { 
                            $event["role".$sessionrole["role_id"]."id"] = $sessionrole["role_id"];
                            $event["role".$sessionrole["role_id"]."min_quantity"] = $sessionrole["min_quantity"];
                            $event["role".$sessionrole["role_id"]."max_quantity"] = $sessionrole["max_quantity"];
                            $event["role".$sessionrole["role_id"]."booked"] = 0;
                            foreach ($bookings as $booking) {
                                if ($booking["sessionrole_id"] == $sessionrole["id"]) { 
                                    $event["role".$sessionrole["role_id"]."booked"] = 1;
                                    break;  
                                }
                            }
                            $event["role".$sessionrole["role_id"]."max_quantity"] = $sessionrole["max_quantity"];
                            break;
                        }
                    }
                }
            }
        }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("id"=>"");
                                // "dowaccess"=>"");
     }
    protected function addtonames($event){
            $this->names[$event["id"]] = $event["name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        // Note the fieldnum parameter should equal the position of the field in the field array in the relevant table Class
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $parentdata = array_combine(array_column($this->parents,"id"),array_column($this->parents,"name"));
        $formfields ="  <input type='hidden' name='recurrence' data-fnum='7' id='recurrence'  value='Once-only' />\n";
        $formfields .="  <input type='hidden' name='starttime' data-fnum='3' id='starttime'  value='' />\n";
        $formfields .="  <input type='hidden' name='endtime' data-fnum='4' id='endtime'  value='' />\n";
        $formfields .="  <input type='hidden' name='eventgroup' data-fnum='26' id='eventgroup'  value='0' />\n";
        $formfields .="  <input type='hidden' name='groupindex' data-fnum='27' id='groupindex'  value='0' />\n";
        $formfields .="  <input type='hidden' name='cellsperrow' data-fnum='28' id='cellsperrow'  value='6' />\n";
        $formfields .="  <input type='hidden' name='sessiondepth' data-fnum='29' id='sessiondepth'  value='99' />\n";
        $formfields .="  <input type='hidden' name='leadtime' data-fnum='5' id='leadtime'  value='99' />\n";
        $formfields .="  <input type='hidden' name='publishedleadtime' data-fnum='6' id='publishedleadtime'  value='99' />\n";
        $optn = [];
        // $button = '<div class="vols-tablecell vols-width-100 aligncenter"><div id="buildsessions" class="clickable action doitbg" style"height: auto">Build</div></div>';
        // $heading = '<div style="display:inline-grid;grid-template-columns:1fr 1fr;align-items:center;"><div style="margin-right:20px">Build sessions:  </div>'.$button.'</div>';
        // $formfields  = $this->component->rendersectionheading($heading,"","","","","","","","",true);
        $formfields .= $this->component->buildinputrow("name",2,"",'Name','Name',20,64,true,'','');
        $formfields .= $this->component->buildselectrow("page_id",1,1,$this->parentname,$parentdata,"",$optionsout,false,false,true,false,'',false);
        $cellclass = " vols-overflow-show ";
        $fromdate = $this->component->renderdateinput("startdate",'','','','','',false,'',"",26,false,false,false); 
        $formfields .= $this->component->renderformrow("startdate","","Date",false,'','','',$fromdate,'',$cellclass,'','','','','','','','','','vols-tablerow '); 
        $this->component->setwidths (30,20,50);
        $formfields .="  <input type='hidden' name='enddate' data-fnum='25' id='enddate'  value='99' />\n";
        // ======================================recurrence section
        $formfields .= '</div>';
        $this->resetwidths();        
        if ($this->isadmin || in_array($this->pagenum."||ROLES",$rights)) {
            $fn = 30;
            $script = <<<JS
                    jQuery("#showrowsbtn").on("click",function (){
                        if ($(this).text() === "Show linked") {
                            $(this).text("Show all");
                            jQuery("#dataspace [id^=link_role]").addClass("hidden");
                            jQuery("#dataspace [id^=link_role] input[type='checkbox']:checked").parent().parent().removeClass("hidden");        
                        } else {
                            $(this).text("Show linked");        
                            jQuery("#dataspace [id^=link_role]").removeClass("hidden");
                        }
                    });
                JS;
            $buttons = ["rightid"=>"showrowsbtn","righttext"=>"Show linked","rightscript"=>$script,"leftid"=>"","lefttext"=>"","leftscript"=>""];
            $formfields .= $this->component->rendersectionheading("Tasks",nomenu:true,buttons:$buttons);
            $cbs = "";
            $checkboxes = '';
            foreach ($this->roles as $id=>$role) {
                // // $iseventrole = false;
                // foreach ($this->eventroles as $eventrole) {
                //     if ($iseventrole = ($eventrole["role_id"] === $role["id"])) {
                //          break;
                //     }
                // }
                $rolename = "link_role".$role["id"];
                $rid = $this->component->rendercheckbox($rolename,1,0,'',false,$fn++,false,'','',false,false,false);
                $checkboxes .= '<input type="hidden" name="'.$rolename.'"  value=false />';
                $min = $this->component->rendertextinput($rolename."_min_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $max = $this->component->rendertextinput($rolename."_max_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $rolefields = $rid." &nbsp; &nbsp;min &nbsp;".$min." &nbsp; &nbsp;max &nbsp;".$max;
                // $rolerows[$id]= $this->component->renderformrow($id,"",$role["name"],false,"","","",$rolefields,'','','',"Specify the Volunteer requirements from this Role",'','','','','','','','');
                $formfields .= $this->component->renderformrow("link_role".$id."row","",$role["name"],false,"","","",$rolefields,'','','',"Specify the Volunteer requirements from this Role",'','','','','','','','');
            }
        }
        return $formfields;
     }
    public function formscript() {
        // passive validation of email and phone number when being entered
          //    Thus the SUBCLASS should do the following:
          //        1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
          //        2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific event handlers, initialise form-specific (third party) components
          //        3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
          //        4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
          //        5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
          //        6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
          //        7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
          //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
        $postloadfieldsscript = <<<SCRIPT
            {$this->loaddowfieldscript} 
            // find the Recurrence period radio button to check based on the data 
            const recurrenceval = jQuery("#recurrence").val();  // loaded from the hidden fields or set by user
            // let radioarray =  ["Once-only","Daily","Weekly","Monthly","Yearly"];
            // let btnnum = radioarray.indexOf(recurrenceval); 
            const radioid = "#rb"+ recurrenceval; // this is the button to be checked
            jQuery(radioid).prop("checked", true).trigger("click"); 
            showoptions(recurrenceval);

            const dailyoptionid = "#do"+jQuery("#dailyoption").val();
            jQuery(dailyoptionid).prop("checked", true)
            const monthoptionid = "#mo"+jQuery("#monthlyoption").val();
            jQuery(monthoptionid).prop("checked", true)
            const yearlyoptionid = "#yo"+jQuery("#yearlyoption").val();
            jQuery(yearlyoptionid).prop("checked", true)

            if (jQuery("#showrowsbtn").text() === "Show linked") {
                jQuery("#dataspace [id^=link_role]").removeClass("hidden");
            } else {
                jQuery("#dataspace [id^=link_role]").addClass("hidden");
                jQuery("#dataspace [id^=link_role] input[type='checkbox']:checked").parent().parent().removeClass("hidden");        
            }

        SCRIPT;
        $postclearfieldsscript = <<<SCRIPT

                        jQuery("input[type='checkbox']").prop("checked",false);
                        $('input:radio').each(function () { $(this).prop('checked', false); });
        SCRIPT;
        $presavescript = <<<SCRIPT
            jQuery("#formerror").html("") ;
            {$this->loaddowvariablescript}
            // now recover the index from the 'checked' radio to 
            let thisval = jQuery("input[type='radio'][name='rb']:checked").val();
            jQuery("#recurrence").val(thisval);
            
            thisval = jQuery("input[type='radio'][name='dayopt']:checked").val();
            jQuery("#dailyoption")   .val(thisval);
            
            thisval = jQuery("input[type='radio'][name='monopt']:checked").val();
            jQuery("#monthlyoption") .val(thisval);
            
            thisval = jQuery("input[type='radio'][name='yearopt']:checked").val();
            jQuery("#yearlyoption") .val(thisval);

        SCRIPT;
        $disablescript = "";
        $onloadscript = <<<SCRIPT
            // need to display the appropriate recurrence options based on the recurrence period
            jQuery("input[type='radio'][name='rb']").click(function() {
                const recurrenceval = jQuery("input[type='radio'][name='rb']:checked").val();
                showoptions(recurrenceval);
            }) 
            jQuery("#buildsessions").on( "click", function(event) {
                setallinactivestatus(1,1,0,0,0,0);
                $("#action").val("buildsessions");
                jQuery("#{$this->formname}").trigger("submit");
            });




        SCRIPT;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript,
                                    $disablescript,
                                    $onloadscript
                                    ); 
        $script .= <<<SCRIPT
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
            }
            function showoptions(recurrenceval) {
                let optionblock;
                switch (recurrenceval) {
                    case "Once-only": break;
                    case "Daily": optionblock = "#dailyrecurrence"; break;
                    case "Weekly": optionblock = "#weeklyrecurrence"; break;
                    case "Monthly": optionblock = "#monthlyrecurrence"; break;
                    /* case "Yearly": optionblock = "#yearlyrecurrence"; break; */
                    default: optionblock = ""
                }
                jQuery(".periodic-ocurrence").removeClass("show").addClass("hide");
                if (optionblock !== "") {
                    jQuery(optionblock).removeClass("hide").addClass("show");
                }                 
            }
         SCRIPT;
        return $script;
     }
}
