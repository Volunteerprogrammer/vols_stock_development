<?php
namespace app\view\form;
use \lib\StdLib as lib;
class FunctionForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $fields = [];
    protected $formname = "eventform";
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
    public function init( $session,$events=[],$parents="",$trace=false,$roles='',$eventroles='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($eventroles)) {  // add the role_ids for the user's roles in a new element in each $user array
            $this->addlinkstodata($events,$roles,$eventroles,$trace);
        }
        parent::init($session,$events,$parents,$trace);
        $this->roles =$roles;
        $this->eventroles = $eventroles;
        if ($this->trace||$trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function addlinkstodata(&$events=[],$roles=[],$eventroles=[],$trace=false) {
        foreach ($events as &$event) {
            foreach ($roles as $role) {
                $ur=0;
                foreach ($eventroles as $eventrole) {
                    if ($event["id"] === $eventrole["event_id"] && $role["id"] === $eventrole["role_id"] ) {
                        $ur = 1 ;
                        break;
                    }
                }
                $event["role".$role["id"]."id"] = $ur;
                $event["role".$role["id"]."min_quantity"] = $ur?$eventrole["min_quantity"]:0;
                $event["role".$role["id"]."max_quantity"] = $ur?$eventrole["max_quantity"]:0;
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
        $optn = [];
        // $button = '<div class="vols-tablecell vols-width-100 aligncenter"><div id="buildsessions" class="clickable action doitbg" style"height: auto">Build</div></div>';
        // $heading = '<div style="display:inline-grid;grid-template-columns:1fr 1fr;align-items:center;"><div style="margin-right:20px">Build sessions:  </div>'.$button.'</div>';
        // $formfields  = $this->component->rendersectionheading($heading,"","","","","","","","",true);
        $formfields = $this->component->buildinputrow("name",2,"",'Name','Name',20,64,true,'','');
        $formfields .= $this->component->buildselectrow("page_id",1,1,$this->parentname,$parentdata,"",$optionsout,false,false,true,false,'',false);
        $input = $this->component->renderdateinput("starttime","time","","","",1,false,"","",3,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"Start Time",false,"","","starttime",$input);
        $input = $this->component->renderdateinput("endtime","time","","","",1,false,"","",4,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"End Time",false,"","","endtime",$input);
        $this->component->setwidths (30,20,50);
        $formfields .= $this->component->buildinputrow("eventgroup",26,"",'Event group','',3,3,false,'','The numbered Group that contains this event in a multi-group, multi-event roster page (1,2,...)? Events within a Group will display across the page if the screen width allows it.');
        $formfields .= $this->component->buildinputrow("groupindex",27,"",'Group Index','',3,3,true,'','The position of this Event in its Event Group (1,2,...).');
        $formfields .= $this->component->buildinputrow("cellsperrow",28,"",'Cells per row','',3,3,false,'','The number of Volunteer cells to display per row in the Roster page (max 6). This impacts the width of the event. If more Volunteers are required per session, the sessions will contain multiple rows of Volunteer cells, as required.');
        $formfields .= $this->component->buildinputrow("sessiondepth",29,"",'Session depth','',3,3,true,'','This determines how many sessions will be displayed for the parent event per roster page?');
        $formfields .= $this->component->buildinputrow("leadtime",5,"",'Leadtime (weeks)','',3,3,true,'','How many weeks ahead of today should the system <strong>generate</strong> sessions?');
        $formfields .= $this->component->buildinputrow("publishedleadtime",6,"",'Published Leadtime','',3,3,true,'','How many weeks ahead of today should the system <strong>publish</strong> sessions?');
        $cellclass = " vols-overflow-show ";
        $fromdate = $this->component->renderdateinput("startdate",'','','','','',false,'',"",24,false,false,false);
        $formfields .= $this->component->renderformrow("startdate","","First Date",false,'','','',$fromdate,'',$cellclass,'','The date of the first session for this event.','','','','','','','','vols-tablerow ');
        $todate = $this->component->renderdateinput("enddate",'','','','','',false,'',"",25,false,false,false);
        $formfields .= $this->component->renderformrow("enddate","","Final Date",false,'','','',$todate,'',$cellclass,'','The date of the last session for this event. Use 31/12/2099 to represent an indefinite period.','','','','','','','','vols-tablerow ');

        // ======================================recurrence section
        $formfields .= $this->component->rendersectionheading("Recurrence","","","","","","","","",true);
        $this->component->setwidths (30,70,0);
        $formfields .="  <input type='hidden' name='recurrence' data-fnum='7' id='recurrence'  value='' />\n";
        // because the recurrence field is an enum, the value for the radion button must be the text element, not its index
        $buttons = [["Once-only"=>"Once-only"],["Daily"=>"Daily"],["Weekly"=>"Weekly"],["Monthly"=>"Monthly"]]; //,["Yearly"=>"Yearly"]
        $rb  = $this->component->renderradiobuttons("rb",$buttons,0,"",999,true,"rb");
        $formfields .= $this->component->renderformrow('recurrencerow',"","Recurring period",true,'','','',$rb);
        for($d=1;$d<=31;$d++) {
            $dom[$d]=$d;
        }
        $fn = 10;

        // DAILY OPTIONS==================================================================   10/11
        $formfields .= '<div id="dailyrecurrence" class="periodic-ocurrence">';
            $formfields .="  <input type='hidden' data-fnum='8' name='dailyoption' id='dailyoption'  value='' />\n";
            $dailyinterval =  $this->component->rendertextinput("dailyinterval",3,3,"1",false,"",'','vols-form-input',9,false,false,false,1,);
            $dailyintervalinput =  " Every &nbsp; {$dailyinterval} &nbsp; day(s)";
            $buttons = [[$dailyintervalinput => 0],["Every weekday"=>1]];
            $dailyoptions  = $this->component->renderradiobuttons("dayopt",$buttons,0,"",999,false,'do',false);        
            $formfields .= $this->component->renderformrow('dailyrow',"","Details",false,'','','',$dailyoptions,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // WEEKLY OPTIONS===============================================================  12/13
        $formfields .= '<div id="weeklyrecurrence" class="periodic-ocurrence">';
            $weeklygroup  =  '<div class="vols-form-radiobuttons vols-width-100 ">';
            $weeklygroup  .=  "Recur every &nbsp; ".$this->component->rendertextinput("weeklyinterval",3,3,"1",false,"",'','vols-form-input',10,false,false,false,1,' week(s) on: ')."</div>";
            $weeklygroup .= $this->component->dayofweekcheckboxes("weeklydow",11,"","",0,false,$this->loaddowfieldscript,$this->loaddowvariablescript,"wdow",true);
            $formfields  .= $this->component->renderformrow('weeklyrow',"","Details",false,'','','',$weeklygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // MONTHLY OPTIONS=============================================================== 14..19
        $ordinalvals = ["first","second","third","fourth","last"];
        $daynames = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"];

        $formfields .= '<div id="monthlyrecurrence" class="periodic-ocurrence">';
            $formfields .="  <input type='hidden' data-fnum='12' name='monthlyoption' id='monthlyoption' value='' />\n";
            $monthdaynums = $this->component->renderdropdown("monthlydayofmonth",1,$optn,false,false,false,false,$dom,'',false,'','',false,13);
            $monthlyinterval0 = $this->component->rendertextinput("monthlyinterval0",3,3,"1",false,"",'','vols-form-input',14,false,false,false,1,'month(s)');
            $monthlyoption0 = "Day ".$monthdaynums." of every ".$monthlyinterval0;

            $monthordinaldropdown = $this->component->renderdropdown("monthlywhichdow",1,$optn,false,false,false,false,$ordinalvals,'',false,'','',false,15);
            $monthdaynamesdropdown = $this->component->renderdropdown("monthlydow",1,$optn,false,false,false,false,$daynames,'',false,'','',false,16);
            $monthlyinterval1 = $this->component->rendertextinput("monthlyinterval1",3,3,"1",false,"",'','vols-form-input',17,false,false,false,1,'month(s)');
            $monthlyoption1 = $monthordinaldropdown." &nbsp; ".$monthdaynamesdropdown." &nbsp; of every &nbsp; ".$monthlyinterval1;
            $buttons = [[$monthlyoption0=>0],[$monthlyoption1 =>1]];
            $monthlygroup  = $this->component->renderradiobuttons("monopt",$buttons,0,"",999,false,'mo',false);
            $formfields .= $this->component->renderformrow('monthlyrow',"","Details",false,'','','',$monthlygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // // YEARLY OPTIONS=============================================================== 20..25
        // $formfields .= '<div id="yearlyrecurrence" class="periodic-ocurrence">';
        //     $formfields .="  <input type='hidden' data-fnum='20' name='yearlyoption' id='yearlyoption' value='' />\n";
        //     // $yearlygroup  =  "Recur every ".$this->component->rendertextinput("yearlymultiple",3,3,"1",false,"",'','vols-form-input',$fn++,false,false,false,1,' year(s)');
        //     $monthnames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        //     $yeardaynumdropdown = $this->component->renderdropdown("yearlydom",1,$optn,false,false,false,false,$dom,'',false,'','',false,21);
        //     $yearmonthnamesdropdown0 = $this->component->renderdropdown("yearlymonth0",1,$optn,false,false,false,false,$monthnames,'',false,'','',false,22);
        //     $yearlyoption0 = " On: &nbsp; ".$yeardaynumdropdown." &nbsp; ".$yearmonthnamesdropdown0;
        //     $yearordinaldropdown = $this->component->renderdropdown("yearlywhichdom",1,$optn,false,false,false,false,$ordinalvals,'',false,'','',false,23);
        //     $yeardaynamesdropdown = $this->component->renderdropdown("yearlywhichday",1,$optn,false,false,false,false,$daynames,'',false,'','',false,24);
        //     $yearmonthnamesdropdown1 = $this->component->renderdropdown("yearlymonth1",1,$optn,false,false,false,false,$monthnames,'',false,'','',false,25);
        //     $yearlyoption1 = " On the &nbsp;".$yearordinaldropdown." &nbsp; ".$yeardaynamesdropdown."&nbsp; of  &nbsp;".$yearmonthnamesdropdown1;
        //     $buttons = [[$yearlyoption0 => 0],[$yearlyoption1 =>1]];
        //     $yearlyoptions  = $this->component->renderradiobuttons("yearopt",$buttons,0,"",999,false,'yo',false);        
        //     $formfields .= $this->component->renderformrow('yearlyrow',"","Details",false,'','','',$yearlyoptions,'',$cellclass,'','','','','','','','','','');
        // $formfields .= '</div>';
        $this->resetwidths();        
        if ($this->isadmin || in_array($this->pagenum."||ROLES",$rights)) {
            $fn = 33;
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
            $formfields .= $this->component->rendersectionheading("Roles",nomenu:true,buttons:$buttons);
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
