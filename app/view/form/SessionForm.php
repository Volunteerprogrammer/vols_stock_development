<?php
namespace app\view\form;
use \lib\StdLib as lib;
class SessionForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 20;
    protected $inputwidth = 45;
    protected $hintwidth = 35;
    protected $fields = [];
    protected $formname = "sessionform";
    protected $objname = "Session";
    protected $parent1name = "Task";
    protected $parent1obj = "task_id";
    protected $secondselectorname="tasklookup";
    protected $sessionid;
    protected $pagenum;
    protected $history;
    protected $names;
    protected $hiddeninputs;
    protected $parents;
    protected $attendances;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($websession,$data=[],$parents="",$trace=false, $volunteers=[],$history=[],$attendances=[] ) {
        if ($this->trace|| $trace) { echo "Enter ".__METHOD__."<br>"; }
        // lib::pr($history);
        $this->history = $history;
        $this->attendances = $attendances;
        // build a string containing the names of the currently booked vols for this session
        // and add it as field[7] within each $volunteer's record in $data
        if (count($volunteers)) { 
            foreach ($data as &$session) {
                $vols="";
                foreach ($volunteers as $volunteer) {
                    if ($volunteer["session_id"] === $session["id"]) {
                        $vols .= ($vols==""?"":", ").$volunteer["volunteername"];
                    }
                }
                $session["volunteers"] = $vols;
            }
        }
        parent::init($websession,$data,$parents,$trace);
        $this->sessionid = $this->requestdata["id"]??0;
     }    
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>"",
                                "task_id"=>"",
                                "start"=>"",
                                "finish"=>"",
                                "is_holiday"=>"",
                                "holiday_name"=>"",
                                "published"=>""
                            );
        // because we have checkbox fields, we need to create hidden versions of them with the same name
        // with value false because under HTML unchecked checkboxes are not included in POST parameters
        // If the checkbox is checked, it will override this hidden version of it in the POST parameters
        $this->hiddeninputs ='<input type="hidden" name="is_holiday"  value=false /><input type="hidden" name="published"  value=false />';
     }
    protected function addtonames($session){
        // $this->names contains a list of all records to be used in the record selector on the editor's header
        // we might want to add a few data attributes to allow us to filter the select list. 
        // The FormComponent class looks for the "||" separator in the text 
        // Anything following that separator is removed from the $text and added to the interior of the <option>,
        // so it must be valid HTML for <option> attributes - e.g. "data-colour="green". 
        // You have to write JS to utilise these attributes.
        $date=date_create($session["start"]);
        $inpast = ($session["start"] < date("Y-m-d"))?"1":"0";
        $this->names[$session["id"]] = date_format($date,"D,  j M  Y")."|| data-task='{$session['task_id']}' data-published='{$session['published']}' data-past='{$inpast}'";
     }         
    protected function addtohidden(){ 
        // add display-only history of all volunteer booking activity and all client attendamces 
        // for all sessions to hidden fields - called from StdCRUDform
        // these hiddem fields are used to populate the display for each session as it's loaded
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // markup the data before hiding it    
        $historyrows = $this->formathistory($this->history); 
        $sessids = "";
        $actiondivs = "";
        foreach ($historyrows as $id => $sessionactions) {
            $sessids .= $id."!!";
            $actiondivs .= $sessionactions."!!";
        }
        $myhidden  = '<div id="js-sessionids">'.$sessids.'</div>'."\n";
        $myhidden .= '<div id="js-bookingactions">'.$actiondivs.'</div>'."\n";
        $attendancerows = $this->formatattendance($this->attendances,"session_id",[["client",40],["additional householders",40]]);
        $sessids = "";
        $attendancedivs = "";
        foreach ($attendancerows as $id => $row) {
            $sessids .= $id."!!";
            $attendancedivs .= $row."!!";
        }
        $myhidden  .= '<div id="js-attendancesessionids">'.$sessids.'</div>'."\n";
        $myhidden .= '<div id="js-attendances">'.$attendancedivs.'</div>'."\n";
        return $myhidden;
     }
    public function formathistory($historyrowdata){
        // the output from this function is used by both the session form and the Roster page
        $historyrows = [];
        $odd = true;
        foreach ($historyrowdata as $value) { 
            // $value[0] = session_id, $value[1] = string of "!!" delimited actions for the session
            $sid = $value["session_id"];
            $actions = explode("!!",$value["actions"]);
            $historyrows[$sid] = '   <div class="vols-tablerow bookinghistoryrow vols-row-headings">'
                                    .$this->component->rendercell("",'<strong>Volunteer</strong>',"vols-tablecell historycell","25",'',0) 
                                    .$this->component->rendercell("",'<strong>Action</strong>',"vols-tablecell historycell","10",'',0)
                                    .$this->component->rendercell("",'<strong>Performed By</strong>',"vols-tablecell historycell","25",'',0)
                                    .$this->component->rendercell("",'<strong>at</strong>',"vols-tablecell historycell","40",'',0)
                                    .'</div>'; 
            $status=[];
            $actionrows = [];
            // build array of html rows from the $data array
            foreach ($actions as $actionkey=>$action) {
                $cells = explode("|", $action);
                // maintain array holding the latest action for each client (assumes chrono order in actions)
                // finally this will hold the last action by each client - book or delete 
                $status[$cells[1]] = ["action"=>$cells[2],"key"=>$actionkey]; 
                if (is_array($cells) ) { //&& count($cells) == 5
                    $historyrow  = $this->component->rendercell("",$cells[1],"","25",'',0); 
                    $historyrow .= $this->component->rendercell("",$cells[2],"","10",'',0) ;
                    $historyrow .= $this->component->rendercell("",$cells[3]??"?","","25",'',0) ;
                    $historyrow .= $this->component->rendercell("",$cells[4]??"?","","40",'',0) ;
                    $actionrows[$actionkey]  =  '<div class="vols-tablerow bookinghistoryrow '.($odd?"vols-row-odd":"vols-row-even").'" styleplaceholder >'.$historyrow.'</div>'; 
                }
                $odd = !$odd;
            }
            // colour last rows for all clients whose last action was "book"
            foreach ($actions as $actionkey=>$action) {
                $cells = explode("|", $action);
                if (is_array($cells) ) {
                    if ($status[$cells[1]]["key"] == $actionkey && $status[$cells[1]]["action"] == 'Booked') {
                       $actionrows[$actionkey] = str_replace("styleplaceholder"," style='background-color: var(--bookingbookedbg)')",$actionrows[$actionkey]);
                    } else if ($actionrows[$actionkey]??0) {
                       $actionrows[$actionkey] = str_replace("styleplaceholder","",$actionrows[$actionkey]);
                    } 
                }
            }
            $historyrows[$sid] .= '<div id="actions">';
            foreach ($actionrows as $action) {
                $historyrows[$sid] .=  $action;
            }
            $historyrows[$sid] .= '</div>'; // closing #actions
        }
        return $historyrows;
     }
    public function formatattendance($data,$idname,$headings){
        $rowmarkup = [];
        $odd = true;
            // lib::pr($data,$headings);
        foreach ($data as $rowinfo) { // $rowinfo[0] = session_id, $rowinfo[1] = string of "!!" delimited actions for the session
            $sessionid = $rowinfo["session_id"];
            $rowdata = explode("|",$rowinfo["clients"]);
            // First add heading row
            $rowmarkup[$sessionid] = '   <div class="vols-tablerow bookinghistoryrow vols-row-headings">';
            foreach ($headings as $col) {
                $rowmarkup[$sessionid] .= $this->component->rendercell("","<strong>{$col[0]}</strong>","vols-tablecell historycell","{$col[1]}",'',0);
            }
            $rowmarkup[$sessionid] .= '</div>'; 
            // now create data rows
            $status=[];
            $datarows = [];
            // "booking_id|vol|action|byperson|actiontime" =>  [booking_id,vol,action,byperson,actiontime]
            foreach ($rowdata as $key=>$celldata) {
                $cells = explode(",", $celldata);
                $datarows[$key]  =  '<div class="vols-tablerow bookinghistoryrow '.($odd?"vols-row-odd":"vols-row-even").'" styleplaceholder >'; 
                if (is_array($cells) ) { 
                    foreach ($cells as $ckey=>$cell){
                        $datarows[$key] .= $this->component->rendercell("",$cell??"?","",$headings[$ckey][1],'',0);
                    }
                }
                $datarows[$key]  .=  '</div>'; 
                $odd = !$odd;
            }
            foreach ($datarows as $row) {
                $rowmarkup[$sessionid] .=  $row;
            }
        }
        return $rowmarkup;
     }
    private function buildfilterpanel($tasks,&$optionsout) {
        // the formstate contains the values of the filters
        if (isset($this->requestdata["formstate"])) { 
            $formstate = $this->requestdata["formstate"];
        } else {
            $formstate = "2|1|0";
        }
        $fs = explode("|",$formstate);
        $hidden = "<input type='hidden' name='formstate' id='formstate'  value='{$formstate}' />\n";
        // BUILD THE FILTERS PANEL
        $taskfilter = $this->component->renderdropdown("tasklookup",1,$optionsout,'','',false,'',$tasks,$fs[0],false,'nondatainput');
        $filters[] = ["0"=>"Select Task","1"=>$taskfilter ];
        $published = [["All"=>2],["Published"=>1],["Not published"=>0]];
        $publishfilter = $this->component->renderradiobuttons("publishfilter",$published,$fs[1],'nondatainput','',true);
        $filters[] = ["0"=>"Select Status","1"=>$publishfilter ];
        $inpast = [["All"=>2],["Future"=>0],["Past"=>1]];
        $datefilter = $this->component->renderradiobuttons("datefilter",$inpast,$fs[2],'nondatainput','',true);
        $filters[] = ["0"=>"Select Dates","1"=>$datefilter ];
        return $hidden.$this->component->renderfiltersheading("filterpanel","Session Filters",$filters);    
     }
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $tasks = [];
        foreach ($this->parents["task"] as $key => $val) {
            $tasks[$val["id"]] = $val["name"];
        }
        // Add the filter panel
        $optionsout = "";
        $formfields = $this->buildfilterpanel($tasks,$optionsout);
        // now on to the data fields...
        $dtype = "datetime-local";
        $formfields .= $this->component->builddaterow("start",$dtype,'','','',1,2,true,"",'Start','',true,false,false,false);
        $formfields .= $this->component->builddaterow("finish",$dtype,'','','',1,3,false,"",'Finish','',true,false,false,false);

        $formfields.= $this->component->buildselectrow($this->parent1obj,1,1,"Task",$tasks,"",$optionsout,false,false,true,false,'',false);
        $hint = "This field wll be inactive if any volunteers are booked into this session. Delete the bookings to make this session a holiday.";
        $formfields .= $this->component->buildcheckboxrow("is_holiday",1,"",false,4,'Is a holiday',$hint,false,false,false,false);
        $formfields .= $this->component->buildinputrow("holiday_name",5,"",'Holiday Name','Holiday Name',20,64,false,'','');
        $formfields .= $this->component->buildcheckboxrow("published",1,"",false,6,'Is published',"",false,false,false,false);
        $formfields .= $this->component->rendersectionheading("Volunteer booking history","","","volunteers");
        $formfields .= '<div id="history"></div>';
        $formfields .= $this->component->rendersectionheading("Client Attendance","","","");
        $formfields .= '<div id="attendance"></div>';
        $this->preparecommontop(false,false,$this->hiddeninputs,$this->sessionid);
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
          //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false //"#recordselector option[data-task='10'][data-published='1'][data-past='0']"
        $postloadfieldsscript = <<<JS
                const current =  jfield[7]==""? "(No bookings)" : "(Currently booked: "+jfield[7]+")";
                $(".vol-form-headingcontainer .volunteers").html(current);

                const jsessionids       = makearray("#js-sessionids","{$this->recorddelimiter}") ;
                const jbookingactions   = makearray("#js-bookingactions","{$this->recorddelimiter}"); 
                let jsindex = jsessionids.indexOf(selectedid);
                $("#history").html(jsindex == -1? "" : jbookingactions[jsindex]);

                const jsattendancesessionids = makearray("#js-attendancesessionids","{$this->recorddelimiter}") ;
                const jsattendances   = makearray("#js-attendances","{$this->recorddelimiter}"); 
                jsindex = jsattendancesessionids.indexOf(selectedid);
                $("#attendance").html(jsindex == -1? "" : jsattendances[jsindex]);
        JS;
        $postclearfieldsscript = <<<JS
                jQuery("input[type='checkbox']").prop("checked",false);
                $('input:radio').not("#filterpanel input").each(function () { $(this).prop('checked', false); });
                $("#history .vols-row-odd, #history .vols-row-even").remove();
        JS;
        $presavescript = <<<JS

                    jQuery("#formerror").html("") ;
                    jQuery("#task_id").val($("#task_id option:selected").val());
                    jQuery("#is_holiday").val(($("#is_holiday").is(":checked")?1:0));
        JS;
        $disablescript = <<<JS
        
                    let vols = $(".vol-form-headingcontainer .volunteers").html();
                    disableaninputstatus("#is_holiday", vols != "(No bookings)");
        JS;
        $onloadscript = <<<JS
                disableaninputstatus ("#tasklookup",false);
                jQuery("#tasklookup,  input[type='radio'][name='publishfilter'], input[type='radio'][name='datefilter']").on("change",function (){applyfilters();})
                applyfilters();
        JS;
        if ($this->sessionid) {
            $onloadscript .= "jQuery('#recordselector').val('{$this->sessionid}').trigger('change');\n";
        }
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
                                    $disablescript,
                                    $onloadscript
                                    );         
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
        
            function getformstate (){
                taskid   = jQuery("#filterpanel #tasklookup").val();
                publish   = jQuery("#filterpanel  input[type='radio'][name='publishfilter']:checked").val();
                daterange = jQuery("#filterpanel  input[type='radio'][name='datefilter']:checked").val();
                return taskid+"|"+publish+"|"+daterange;   
            }
            function applyfilters () {

                jQuery("#recordselector option").addClass("hide").removeClass("show");
                const formstate = getformstate();
                const fs = formstate.split("|");
                const taskid   = fs[0];
                const publish   = fs[1];
                const daterange = fs[2];   
                const pstring  =  publish == "2"?"": ("[data-published='"+publish+"']")
                const dstring  =  daterange == "2"?"": ("[data-past='"+daterange+"']")
                const filterstr = "#recordselector option[data-task='"+taskid+"']"+pstring+dstring;
                const optioncount = jQuery(filterstr).length;
                if (optioncount) {
                    jQuery("#recordselector-NA").removeClass("show").addClass("hide");
                    jQuery(filterstr).toggleClass("show hide");
                    jQuery("#recordselector option.show:first").prop('selected', true);
                    jQuery("#recordselector").trigger("change");
                } else {
                    jQuery("#recordselector-NA").removeClass("show").addClass("hide").prop('selected', true);;
                    jQuery("#recordselector").trigger("change");
               }
                jQuery("#formstate").val(formstate);
            }
            function formhaserrors() {
                let errors = 0;
                if (!(jQuery("#start").val() &&
                     jQuery("#finish").val() && 
                     jQuery("#task_id").val())){ 
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
