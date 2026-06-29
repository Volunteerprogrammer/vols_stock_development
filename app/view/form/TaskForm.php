<?php
namespace app\view\form;
use \lib\StdLib as lib;
class TaskForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $fields = [];
    protected $formname = "taskform";
    protected $objname = "Task";
    protected $parentname = "Roster";
    protected $parentobj = "page_id";
    protected $pagenum;
    protected $names;
    protected $parents;
    protected $taskid;
    protected $roles;
    protected $rolerows;
    protected $taskroles;
    protected $rosteralerts = [];
    protected $loaddowfieldscript;
    protected $loaddowvariablescript;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init( $session,$tasks=[],$parents="",$trace=false,$roles='',$taskroles='',$rosteralerts=[]) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($taskroles)) {
            $this->addlinkstodata($tasks,$roles,$taskroles,$trace);
         }
        parent::init($session,$tasks,$parents,$trace);
        $this->roles =$roles;
        $this->taskroles = $taskroles;
        $this->rosteralerts = is_array($rosteralerts) ? $rosteralerts : [];
        $this->taskid = $this->requestdata["id"]??"";
        if ($this->trace||$trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function addlinkstodata(&$tasks=[],$roles=[],$taskroles=[],$trace=false) {
        foreach ($tasks as &$task) {
            foreach ($roles as $role) {
                $ur=0;
                foreach ($taskroles as $taskrole) {
                    if ($task["id"] === $taskrole["task_id"] && $role["id"] === $taskrole["role_id"] ) {
                        $ur = 1 ;
                        break;
                    }
                }
                $task["role".$role["id"]."id"] = $ur;
                $task["role".$role["id"]."min_quantity"] = $ur?$taskrole["min_quantity"]:0;
                $task["role".$role["id"]."max_quantity"] = $ur?$taskrole["max_quantity"]:0;
            }
        }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("id"=>"");
     }
    protected function addtonames($task){
            $this->names[$task["id"]] = $task["name"];
     }
    protected function addtohidden() {
        $fd = $this->fielddelimiter;
        $rd = $this->recorddelimiter;
        $trashicon = $this->component->geticon("trash");
        $template = <<<HTML
            <div id="alert_row##alertid" class="vols-tablerow ##oddeven alertrow childcontainer grouped alertgroup">
                <input type="hidden" name="alert_id##alertid" value="##alertid">
                <div class="vols-tablecell vols-vertical-center vols-width-5"></div>
                <div class="vols-tablecell vols-vertical-center vols-width-85">
                    For <select id="alert_role##alertid" name="alert_role##alertid" class="vols-form-select"></select>
                    send alert if fewer than
                    <input type="number" id="alert_lev##alertid" name="alert_lev##alertid" class="vols-form-input" style="width:2.5em" size="2" maxlength="2" value="##level">
                    bookings
                    <input type="number" id="alert_per##alertid" name="alert_per##alertid" class="vols-form-input" style="width:3em" size="3" maxlength="4" value="##period">
                    days before.
                </div>
                <div class="vols-tablecell childdeletecell">
                    <div id="delete_alert##alertid" class="floatright activeicon trashsvgcontainer childdeleteicon">{$trashicon}</div>
                </div>
            </div>
        HTML;
        $myhidden = '<div id="alerttemplate">'.$template.'</div>';
        // Alert records grouped by task_id (task_id comes from the JOIN in getalltaskalerts)
        $alerttaskids = '';
        $alertdivs    = '';
        $prevtaskid   = '0';
        foreach ($this->alldata as $task) {
            foreach ($this->rosteralerts as $alert) {
                if ($task["id"] == $alert["task_id"]) {
                    if ($task["id"] != $prevtaskid) {
                        if ($prevtaskid != '0') { $alertdivs .= $rd; }
                        $alerttaskids .= $task["id"].$rd;
                        $prevtaskid = $task["id"];
                    }
                    $alertdivs .= $alert["id"].$fd.$alert["task_role_id"].$fd.$alert["period"].$fd.$alert["level"].$fd;
                }
            }
        }
        $alertdivs .= $rd;
        $myhidden .= '<div id="js-alerttaskids">'.$alerttaskids.'</div>'."\n";
        $myhidden .= '<div id="js-alertdata">'.$alertdivs.'</div>'."\n";
        // Task-role records with role names for JS role selector (task_role_id|task_id|role_name)
        $rolelookup = array_column($this->roles, 'name', 'id');
        $taskroledata = '';
        foreach ($this->taskroles as $tr) {
            $rolename = $rolelookup[$tr['role_id']] ?? '';
            $taskroledata .= $tr['id'].$fd.$tr['task_id'].$fd.$rolename.$rd;
        }
        $myhidden .= '<div id="js-taskroledata">'.$taskroledata.'</div>'."\n";
        return $myhidden;
     }
    public function buildinputs($rights=[], $trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $parentdata = array_combine(array_column($this->parents,"id"),array_column($this->parents,"name"));
        $parentdata = [NULL=>""] + $parentdata;
        $optn = [];
        $formfields = $this->component->buildinputrow("name",2,"",'Name','Name',20,64,true,'','');
        // ======================================General section
        $formfields .= $this->component->rendersectionheading("General",inputgroup:"generalgroup");
        $formfields .= $this->component->buildselectrow("page_id",1,1,$this->parentname,$parentdata,"",$optionsout,false,false,true,false,'',false);
        $input = $this->component->renderdateinput("starttime","time","","","",1,false,"","",3,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"Start Time",false,"","","starttime",$input);
        $input = $this->component->renderdateinput("endtime","time","","","",1,false,"","",4,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"End Time",false,"","","endtime",$input);
        $this->component->setwidths (30,20,50);
        // ======================================Display section
        $formfields .= $this->component->rendersectionheading("Display",inputgroup:"displaygroup");
        $formfields .= $this->component->buildinputrow("taskgroup",22,"",'Task group','',3,3,false,'','The numbered Group that contains this task in a multi-group, multi-task roster page (1,2,...)? Tasks within a Group will display across the page if the screen width allows it.');
        $formfields .= $this->component->buildinputrow("groupindex",23,"",'Group position','',3,3,false,'','The position of this Task in its Task Group (1,2,...).');
        $formfields .= $this->component->buildinputrow("cellsperrow",24,"",'Cells per row','',3,3,false,'','The number of Volunteer cells to display per row in the Roster page (max 6). This impacts the width of the task. If more Volunteers are required per session, the sessions will contain multiple rows of Volunteer cells, as required.');
        $formfields .="  <input type='hidden' name='logattendance' data-fnum='27' id='logattendance'  value='' />\n";

        // ======================================recurrence section
        $formfields .= $this->component->rendersectionheading("Recurrence",inputgroup:"recurrencegroup");
        $cellclass = " vols-overflow-show ";
        $this->component->setwidths (30,70,0);
        $formfields .="  <input type='hidden' name='recurrence' data-fnum='5' id='recurrence'  value='' />\n";
        $buttons = [["Once-only"=>"Once-only"],["Daily"=>"Daily"],["Weekly"=>"Weekly"],["Monthly"=>"Monthly"]];
        $rb  = $this->component->renderradiobuttons("rb",$buttons,0,"",999,true,"rb",required:true);
        $formfields .= $this->component->renderformrow('recurrencerow',"","Recurring period",true,'','','',$rb);
        for($d=1;$d<=31;$d++) {
            $dom[$d]=$d;
        }

        // DAILY OPTIONS==================================================================   6/7
        $formfields .= '<div id="dailyrecurrence" class="periodic-ocurrence">';
            $formfields .="  <input type='hidden' data-fnum='6' name='dailyoption' id='dailyoption'  value='' />\n";
            $dailyinterval =  $this->component->rendertextinput("dailyinterval",3,3,"1",false,"",'','vols-form-input',7,false,false,false,1,);
            $dailyintervalinput =  " Every &nbsp; {$dailyinterval} &nbsp; day(s)";
            $buttons = [[$dailyintervalinput => 0],["Every weekday"=>1]];
            $dailyoptions  = $this->component->renderradiobuttons("dayopt",$buttons,0,"",999,false,'do',false);
            $formfields .= $this->component->renderformrow('dailyrow',"","Details",false,'','','',$dailyoptions,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // WEEKLY OPTIONS===============================================================  8/9
        $formfields .= '<div id="weeklyrecurrence" class="periodic-ocurrence">';
            $weeklygroup  =  '<div class="vols-form-radiobuttons vols-width-95 ">';
            $weeklygroup  .=  "Recur every &nbsp; ".$this->component->rendertextinput("weeklyinterval",3,3,"1",false,"",'','vols-form-input',8,false,false,false,1,' week(s) on the: ')."</div>";
            $windex = "<div id='weeklyindexwrapper' class='vols-float-left'><select name='weeklyindex' id='weeklyindex' class='vols-form-select hide' size='1' required='' data-fnum='25' disabled=''></select></div>";
            $weeklygroup .= $this->component->dayofweekcheckboxes("weeklydow",9,$windex,"",0,false,$this->loaddowfieldscript,$this->loaddowvariablescript,"wdow",true);
            $formfields  .= $this->component->renderformrow('weeklyrow',"","Details",false,'','','',$weeklygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // MONTHLY OPTIONS=============================================================== 10..15
        $ordinalvals = ["first","second","third","fourth","last"];
        $daynames = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"];

        $formfields .= '<div id="monthlyrecurrence" class="periodic-ocurrence">';
        $formfields .="  <input type='hidden' data-fnum='10' name='monthlyoption' id='monthlyoption' value='' />\n";
        $monthdaynums = $this->component->renderdropdown("monthlydayofmonth",1,$optn,false,false,false,false,$dom,'',false,'','',false,11);
        $monthlyinterval0 = $this->component->rendertextinput("monthlyinterval0",3,3,"1",false,"",'','vols-form-input',12,false,false,false,1,'month(s)');
        $monthlyoption0 = "Day ".$monthdaynums." of every ".$monthlyinterval0;

        $monthordinaldropdown = $this->component->renderdropdown("monthlywhichdow",1,$optn,false,false,false,false,$ordinalvals,'',false,'','',false,13);
        $monthdaynamesdropdown = $this->component->renderdropdown("monthlydow",1,$optn,false,false,false,false,$daynames,'',false,'','',false,14);
        $monthlyinterval1 = $this->component->rendertextinput("monthlyinterval1",3,3,"1",false,"",'','vols-form-input',15,false,false,false,1,'month(s)');
        $monthlyoption1 = $monthordinaldropdown." &nbsp; ".$monthdaynamesdropdown." &nbsp; of every &nbsp; ".$monthlyinterval1;
        $buttons = [[$monthlyoption0=>0],[$monthlyoption1 =>1]];
        $monthlygroup  = $this->component->renderradiobuttons("monopt",$buttons,0,"",999,false,'mo',false);
        $formfields .= $this->component->renderformrow('monthlyrow',"","Details",false,'','','',$monthlygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // =========================================== Booking Alerts child section
        $formfields .= $this->component->rendersectionheading("Booking Alerts",inputgroup:"alertgroup",addid:"rosteralert");
        $formfields .= '<div id="alerts"></div>';
        if ($this->isadmin || in_array($this->pagenum."||ROLES",$rights)) {
            $fn = 28;
            $buttons = ["rightid"=>"showrowsbtn","righttext"=>"Show A<span class='underlined'>L</span>L","rightscript"=>"","leftid"=>"","lefttext"=>"","leftscript"=>""];
            $heading = "<span id='statustextspan'>ALL</span> Roles";
            $formfields .= $this->component->rendersectionheading($heading,buttons:$buttons);
            $this->component->setwidths (30,40,30);
            $hiddencheckboxes = '';
            foreach ($this->roles as $role) {
                $rolename = "link_role".$role["id"];
                $rcb = $this->component->rendercheckbox($rolename,1,0,'',false,$fn++,false,'','',false,false,false);
                $min = $this->component->rendertextinput($rolename."_min_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $max = $this->component->rendertextinput($rolename."_max_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $rolefields = $rcb." &nbsp; &nbsp;min &nbsp;".$min." &nbsp; &nbsp;max &nbsp;".$max;
                $formfields .= $this->component->renderformrow("link_role".$role["id"]."row","",$role["name"],false,"","","",$rolefields,'','','',"Volunteers needed from this Role",'','','','','','','','');
                $hiddencheckboxes .= '<input type="hidden" name="'.$rolename.'"  value=false />';
            }
        }
        $this->preparecommontop(selecttext:$this->taskid,hiddeninputs:$hiddencheckboxes);
        return $formfields;
     }
    public function formscript() {
        $postloadfieldsscript = <<<JS
                        // this script is already built by the component class as it creates the DOW check boxes
                        {$this->loaddowfieldscript}
                        // find the Recurrence period radio button to check based on the data
                        const recurrenceval = jQuery("#recurrence").val();
                        const radioid = "#rb"+ recurrenceval;
                        jQuery(radioid).prop("checked", true).trigger("click");
                        showoptions(recurrenceval);

                        const dailyoptionid = "#do"+jQuery("#dailyoption").val();
                        jQuery(dailyoptionid).prop("checked", true)
                        const monthoptionid = "#mo"+jQuery("#monthlyoption").val();
                        jQuery(monthoptionid).prop("checked", true)
                        const yearlyoptionid = "#yo"+jQuery("#yearlyoption").val();
                        jQuery(yearlyoptionid).prop("checked", true)

                        displayweeklyindex(jfield[8],jfield[25]);
                        jQuery("#showrowsbtn").data("state", "linked");
                        showhidepages();
                        // --- load booking alert rows ---
                        jQuery("#dataspace div.alertrow.childcontainer").remove();
                        window.currentTaskId = selectedid;
                        const fd = "{$this->fielddelimiter}";
                        const rd = "{$this->recorddelimiter}";
                        const alertTaskIds = makearray("#js-alerttaskids", rd);
                        const alertTaskIndex = alertTaskIds.indexOf(selectedid);
                        if (alertTaskIndex !== -1) {
                            const allAlertData = makearray("#js-alertdata", rd);
                            const thisTaskAlerts = allAlertData[alertTaskIndex] || "";
                            if (thisTaskAlerts !== "") {
                                const alertFields = thisTaskAlerts.split(fd);
                                const alertIds = [];
                                const taskRoleIds = [];
                                let alertsHtml = "";
                                let isodd = true;
                                for (let i = 0; i < alertFields.length - 1; ) {
                                    let newrow = jQuery("#alerttemplate").html();
                                    const alertId    = alertFields[i++];
                                    const taskRoleId = alertFields[i++];
                                    const period     = alertFields[i++];
                                    const level      = alertFields[i++];
                                    newrow = newrow.replaceAll("##alertid", alertId)
                                                   .replaceAll("##period",  period)
                                                   .replaceAll("##level",   level)
                                                   .replaceAll("##oddeven", isodd ? "vols-row-odd" : "vols-row-even");
                                    isodd = !isodd;
                                    alertsHtml += newrow;
                                    alertIds.push(alertId);
                                    taskRoleIds.push(taskRoleId);
                                }
                                jQuery("#alerts").after(alertsHtml);
                                alertIds.forEach((alertId, idx) => {
                                    jQuery("#alert_role" + alertId).html(buildRoleOptions(taskRoleIds[idx]));
                                });
                                jQuery("div.alertrow.childcontainer .childdeleteicon").off().on("click", function(event) {
                                    deletechild(jQuery(this), event);
                                });
                            }
                        }

         JS;
        $postclearfieldsscript = <<<JS

                        jQuery("input[type='checkbox']").prop("checked",false);
                        $('input:radio').each(function () { $(this).prop('checked', false); });
                        jQuery("#dataspace div.alertrow.childcontainer").remove();
        JS;
        $presavescript = <<<JS

                        jQuery("#formerror").html("") ;
                        {$this->loaddowvariablescript}
                        let thisval = jQuery("input[type='radio'][name='rb']:checked").val();
                        jQuery("#recurrence").val(thisval);

                        thisval = jQuery("input[type='radio'][name='dayopt']:checked").val();
                        jQuery("#dailyoption")   .val(thisval);

                        thisval = jQuery("input[type='radio'][name='monopt']:checked").val();
                        jQuery("#monthlyoption") .val(thisval);

                        thisval = jQuery("input[type='radio'][name='yearopt']:checked").val();
                        jQuery("#yearlyoption") .val(thisval);

        JS;
        $disablescript = "";
        $onloadscript = <<<JS
                        jQuery("input[type='radio'][name='rb']").click(function() {
                            const recurrenceval = jQuery("input[type='radio'][name='rb']:checked").val();
                            showoptions(recurrenceval);
                        })
                        jQuery("#buildsessions").on( "click", function(event) {
                            setallinactivestatus(1,1,0,0,0,0);
                            $("#action").val("buildsessions");
                            jQuery("#{$this->formname}").trigger("submit");
                        });
                        jQuery("#weeklyinterval").on("change", function() {
                            const interval = $(this).val();
                            const index = jQuery("#weeklyindex").val();
                            displayweeklyindex(interval,index);
                        });
        JS;
        $script  = $this->vols_masterscript($this->formname,
                                    $this->objname,
                                    true,
                                    true,
                                    true,
                                    false,
                                    '',
                                    $postloadfieldsscript,
                                    $postclearfieldsscript,
                                    false,
                                    '',
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

            function ordinalwords( cardinal ) {
                const ordinals = [ 'zeroth', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'nineth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth'];
                const tens = {
                    20: 'twenty',
                    30: 'thirty',
                    40: 'forty',
                    50: 'fifty',
                    60: 'sixty',
                    70: 'seventy',
                    80: 'eighty',
                    90: 'ninety',
                };
                const ordinalTens = {
                    20: 'twentieth',
                    30: 'thirtieth',
                    40: 'fortieth',
                    50: 'fiftieth',
                    60: 'sixtieth',
                    70: 'seventieth',
                    80: 'eightieth',
                    90: 'ninetieth',
                };

                if( cardinal <= 20 ) {
                    return ordinals[ cardinal ];
                }

                if( cardinal % 10 === 0 ) {
                    return ordinalTens[ cardinal ];
                }

                return tens[ cardinal - ( cardinal % 10 ) ] + ordinals[ cardinal % 10 ];
            }
            function displayweeklyindex(weeklyinterval,weeklyindex) {
                if (weeklyinterval > 1) {
                    let options  = '<option id="weeklyindex-0" value="0" '+(weeklyindex==0?'selected':'') +'>First</option>';
                    options += '<option id="weeklyindex-1" value="1" '+(weeklyindex==1?'selected':'') +'>Second</option>';
                    for (i=3;i<=weeklyinterval;i++) {
                        const ordinal = ordinalwords(i);
                        options += '<option id="weeklyindex-'+(i-1)+'" value="'+(i-1)+'" '+(weeklyindex==(i-1)?'selected':'') +'>'+ordinal+'</option>';
                    }
                    jQuery("#weeklyindex").html(options).removeClass("hidden");
                } else {
                    jQuery("#weeklyindex").html("").addClass("hidden");
                }
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
            }
            function showoptions(recurrenceval) {
                let optionblock;
                switch (recurrenceval) {
                    case "Once-only": break;
                    case "Daily": optionblock = "#dailyrecurrence"; break;
                    case "Weekly": optionblock = "#weeklyrecurrence"; break;
                    case "Monthly": optionblock = "#monthlyrecurrence"; break;
                    default: optionblock = ""
                }
                jQuery(".periodic-ocurrence").removeClass("show").addClass("hide");
                if (optionblock !== "") {
                    jQuery(optionblock).removeClass("hide").addClass("show");
                }
            }
            function buildRoleOptions(selectedTaskRoleId) {
                const fd = "{$this->fielddelimiter}";
                const rd = "{$this->recorddelimiter}";
                const raw = jQuery("#js-taskroledata").text().trim();
                const records = raw ? raw.split(rd).filter(r => r !== "") : [];
                const taskRoles = records.filter(r => r.split(fd)[1] === window.currentTaskId);
                return taskRoles.map(r => {
                    const parts = r.split(fd);
                    const sel = String(parts[0]) === String(selectedTaskRoleId) ? " selected" : "";
                    return `<option value="\${parts[0]}"\${sel}>\${parts[2]}</option>`;
                }).join("");
            }
            function addtogroup(task) {
                const target = jQuery(task.currentTarget);
                const groupname = target.prop("id");
                if (groupname === "rosteralert") {
                    if (!window.currentTaskId) { return; }
                    let newrow = jQuery("#alerttemplate").html();
                    const randid = (-1 * getRandomInt()).toString();
                    newrow = newrow.replaceAll("##alertid", randid)
                                   .replaceAll("##period",  "")
                                   .replaceAll("##level",   "")
                                   .replaceAll("##oddeven", "");
                    target.closest(".vols-tablerow").after(newrow);
                    jQuery("#alert_role" + randid).html(buildRoleOptions(""));
                    jQuery("#alert_row" + randid + " .childdeleteicon").off().on("click", function(event) {
                        deletechild(jQuery(this), event);
                    });
                    if (target.prev(".groupsvgcontainer").hasClass("collapsed")) {
                        target.prev(".groupsvgcontainer").trigger("click");
                    }
                }
            }
            function deletechild(target, event) {
                const alertid = target.prop("id").substring(12).toString();
                const container = jQuery(event.target).closest(".childcontainer");
                container.find("#alert_role" + alertid).val("");
                container.find("#alert_per" + alertid).val("");
                container.find("#alert_lev" + alertid).val("");
                container.addClass("hide content_hidden");
            }
            function getchildnames() { return ["role","Roles"]}
         JS;
        return $script;
     }
}
