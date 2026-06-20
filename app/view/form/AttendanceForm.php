<?php
namespace app\view\form;
use \app\library\AppLib as vlib;
use \lib\StdLib as lib;
class AttendanceForm extends \fw\view\form\Form {
    private $trace= false;
    private $config;  
    private $requestdata;  
    private $clientdata;  
    private $sessiondata;  
    private $taskdata;  
    private $clientsessions;  
    private $session_id;  
    private $menu;  
    private $checkdeletions=1;
    protected $page;              
    public  function __construct(protected FormComponent $component,
                                 protected \fw\view\form\TouchscreenKeyboard $kbd) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function init($sessn){
        parent::init($sessn);
        $this->requestdata = $this->session->getrequestdata();
        $this->config = $this->session->getconfig();
     }
    public  function setdata($clientdata,$sessiondata,$clientsessions,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->clientdata = $clientdata;
        $this->sessiondata = $sessiondata;
        $this->clientsessions = $clientsessions;
        foreach($this->sessiondata as &$session) {
            $d = $session["start"];// "2025/02/17 12:15"
            $session["start"] = substr($d,8,2)."/".substr($d,5,2)."/".substr($d,0,4);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public  function render($pagenum=0,$nextpage='',$subheading='',$rights=[],$isadmin=false,$menu='',$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->isadmin = $isadmin;
        $this->pagenum = $pagenum;
        $header = $this->buildheading($menu,$trace);
        $instructions = $this->buildcenterinstructions($trace);
        $matches = $this->buildmatches($trace);
        $keyboardcontainer = $this->buildcenterkeyboard($trace);
        $rightsidebar = $this->buildrightsidebar($trace);
        // $content needs to contain the header and body sections 
        // for the page. The standard footer is supplied by the 
        // body class
        $content = <<<HTML
                    <div id="headercontainer">
                        {$header}
                    </div>
                    <div id="maincontainer">
                        <div id="attendanceloggingcontainer">
                            <div id="centercontainer">
                                {$matches}
                                {$keyboardcontainer}
                            </div>
                            <div id="rightcontainer">
                                {$rightsidebar}
                            </div>
                        </div>
                    </div>
        HTML;
        $phpclients2d = '[';
        foreach($this->clientdata as $client) {
            $phpclients2d .= "['{$client['id']}','{$client['name']}'],"; 
        }
        $phpclients2d .= ']';
        $phpclientsessions2d = '[';
        foreach($this->clientsessions as $clientsession) {
            $phpclientsessions2d .= "['{$clientsession['id']}','{$clientsession['session_id']}','{$clientsession['client_id']}'],"; 
        }
        $phpclientsessions2d .= ']';

        $content .= $this->pagescript($phpclients2d,$phpclientsessions2d,$this->checkdeletions,false);

        // error_reporting($errorlevel);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;  
     }
    // =========== START rendering header section ====================================
    private function buildheading ($menu,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $pn = $this->pagenum."||";
        $logos = vlib::logopair();
        $selector = $this->buildselector($idx,$this->pagenum==335);//Determines today's session ($idx) and builds session selector
        if ($this->pagenum==335) { // VOLUNTEER ACCESS
            if ($idx !== "") {
                $title = "{$this->sessiondata[$idx]['name']} ".str_replace("/",".",$this->sessiondata[$idx]['start']);
            } else {
                $title = "Sorry. There is no Session today";
            }
        } else {
            $title = "";
        }
        $heading = <<<HTML
                <div id="logocontainer">
                    {$logos}
                </div>    
                <div id="sessiontitle">
                    <div id='sessname'>
                        {$title}
                    </div>
                    {$selector}
                </div>    
                <div id='menucontainer' class='greetingcontainer'>
                    {$menu}
                </div>    
        HTML;
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $heading;
     }
    private function buildselector(&$idx,$hidden=0){
        $targetsession_id = "";
        $idx = "";
        $sessions = ['0'=>"||selected='selected' style='display: none'"];
        $todayDateTime = new \DateTime('now'); 
        $todayDate = $todayDateTime->format('d/m/Y');
        $prevmonth = "";
        $bgstyle = true;
        foreach($this->sessiondata as $key=>&$session) {
            $today = false;
            if (str_contains(trim($session["start"]),$todayDate)) {
                $targetsession_id = "sessionselector-{$session["session_id"]}";
                $idx = $key;
                $today = true;
            }

            $sessionDateobj = new \DateTime(str_replace("/","-",$session["start"])); 
            $sessionDate = $sessionDateobj->format('d M Y');
            $sessionmonth = $sessionDateobj->format('M');
            if ($prevmonth !== $sessionmonth) {
                $bgstyle = !$bgstyle;
                $prevmonth = $sessionmonth;
            }
            $bgcol = $bgstyle?"fff":"ddd";
            if (!$hidden || $today) {
                $sessions[$session["session_id"]] = $sessionDate."   (".$sessionDateobj->format('D').")|| data-date='".$sessionDateobj->format('d.m.Y')."' style='background-color: #".$bgcol."'"; 
            }
        }
        $selectclass = ""; //$hidden?"hidden vols-form-select":"";
        $selector = $this->component->renderdropdown("sessionselector",1,$options,values:$sessions,selection:$targetsession_id,selectclass:$selectclass);
        return $selector;
     } 
    // =========== START rendering main section ====================================
    private function buildcenterinstructions ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content  = <<<HTML
                <div id='register'> 
                    <p>To <strong>REGISTER</strong> a client's attendance:<p> 
                    <ol>
                        <li>Start typing their first name.</li>
                        <li>When you see their name in <strong>Matching Clients</strong>, touch/click it.</li>
                    </ol>
                    <p>They will appear in <strong>Today's Attendees</strong>.<p> 
                </div>
                <div id='remove'> 
                    <p>To <strong>REMOVE</strong> a client from <strong>Today's Attendees</strong>:<p> 
                    <ol>
                        <li>Touch/click their name in the list.</li>
                        <li>A popup will appear. Confirm your decision to remove them.</li>
                    </ol>
                    <p>They will disappear from <strong>Today's Attendees</strong>.<p> 
                </div> 
            HTML;
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function buildmatches ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content  = '<div id="matchingclients">';
        $content  .=    '<div class="heading">Client Search</div>';
        $content  .=    '<div class="list"><ul id="matcheslist"></ul></div>';
        $content  .= '</div>';
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function buildcenterkeyboard ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content  = $this->kbd->render();
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function buildrightsidebar ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content  = '<div id="attendees">';
        $content  .=    '<div class="heading">Today\'s Attendees</div>';
        $content  .=    '<div class="list"><ul id="attendeeslist"></ul></div>';
        $content  .= '</div>';
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function buildfooter ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $pn = $this->pagenum."||";
        $content  = '<div>';
        $content  .= '</div>';
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function pagescript( $phpclients2d, $phpclientsessions2d, $checkdeletions, $trace = false  ) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $script =  <<<JS

            <script>
                let rostersession = { // obj used to store session details and methods
                                    sessionloaded: false,
                                    session_id:0,
                                    setstatus:function (status) {
                                        rostersession.sessionloaded = status;
                                    },
                                    getstatus:function () {
                                        return rostersession.sessionloaded;
                                    },
                                    setid:function (id) {
                                        rostersession.session_id = id;
                                    },
                                    getid:function () {
                                        return rostersession.session_id;
                                    }
                                } 
                const checkdeletions = {$checkdeletions};//boolean
                let jsclients=[];
                let jsclientsessions=[];
                jQuery(function () {  // onload script
                    // populate the data arrays
                    var jsclients2d = {$phpclients2d};
                    var jsclientsessions2d = {$phpclientsessions2d};
                    // intialise the client and clientsession data arrays 
                    jsclients2d.forEach((client)=>{
                         populateclients(client); 
                    });
                    jsclientsessions2d.forEach(function (clientsession){    
                        populateclientsessions(clientsession);
                    });
                    // set event handlers
                    jQuery('#kbdinput').on("change",function() {
                        searchtermchange();
                    });
                    jQuery("#sessionselector").on("change",function()  {
                        sessionchange();
                    });
                    // convert real keystrokes to virtual keyboard touches
                    $(document).on('keydown', function(event) {
                        processkeystroke(event); 
                    });
                    findtodayssession();
                    sessionchange();
                })
                function processkeystroke(event) {
                    switch (event.which) {
                        case 8 : //backspace
                            if (!jQuery("#volsdialog").hasClass("ui-dialog-content")) {
                                event.preventDefault();
                                jQuery("#keyboard div.key-row.SPACE  button[data-key='backspace']").trigger("click");
                            }
                            break;
                        case 27 : // escape
                            event.preventDefault();
                            jQuery("#keyboard div.key-row.SPACE button[data-key='clear']").trigger("click");
                            break;
                        case 32 : // space
                            if (!jQuery("#volsdialog").hasClass("ui-dialog-content")) {
                                event.preventDefault();
                                jQuery("#keyboard div.key-row.SPACE button[data-key='space']").trigger("click");
                            }
                            break;
                        default:
                            if (!jQuery("#volsdialog").hasClass("ui-dialog-content")) {
                                let  chr = String.fromCharCode(event.which).toUpperCase();
                                if (chr.match(/[A-Z\-\.\ ]/i)) {
                                    jQuery("#keyboard div.key-row button[data-key='"+chr+"']").trigger("click");
                                }
                            }
                    }
                }
                function populateclients(client) {
                    jsclients[client[0]] = client[1].toString();
                }
                function populateclientsessions(clientsession) {
                   jsclientsessions[clientsession[0]] = [clientsession[1].toString(),clientsession[2].toString()];
                }
                function makelistitem(clientsession_id,client_id,clientname){
                    return "<li data-clientsessionid='"+clientsession_id+"' data-clientid='"+client_id+"'>"+clientname+"</li>";
                }
                function setlihandlers(makelistsactive) {
                    jQuery("#matchingclients ul li").off();
                    jQuery("#attendeeslist li").off();
                    if (makelistsactive) {
                        jQuery("#matchingclients ul li").on("click", function () {
                            addattendee(jQuery(this));
                        })
                        jQuery("#attendeeslist li").on("click", function () {
                            removeattendee(jQuery(this));
                        })
                    }
                }
 
                function searchtermchange(){
                    const val = jQuery('#kbdinput').val();
                    // jQuery("#kbdmsg").html("'"+val+"': "+rostersession.getstatus()); 
                    if (val === "") { 
                        clearmatches();
                    } else if (val === " F ") { 
                        if (document.fullscreenElement  === null) {
                            enterFullscreen(document.documentElement);
                        } else  {
                            exitFullscreen();
                        }
                        reset();
                    } else {
                        if (rostersession.getstatus() == true) {
                            findmatches()
                        }
                    }                    
                }
                function sessionchange(flag=""){
                    if ((jQuery("#sessionselector option:selected").length == 0) || 
                        (jQuery("#sessionselector option:selected").val() == 0)) {
                        rostersession.setstatus(false);
                        rostersession.setid(0);
                        setlihandlers(0); // REMOVE HANDLERS FROM LISTS
                        jQuery("#tasktitle #sessname").html("Sorry. There is no Session today");
                    }else {
                        rostersession.setstatus(true);
                        const message = "proceed";
                        const sessionname = jQuery("#sessionselector option:selected").text();
                        jQuery("#tasktitle #sessname").html(sessionname);
                        if (flag != message) { // this call is not a dialog.close callback so check the date 
                            if (isinthefuture(sessionname.slice(-10))) {
                                const msg = "This session is in the future. Are you sure you want to work on this session?"
                                jQuery.volsdialog("OKMSG", msg,sessionchange,undefined, "Please note...",{},{minWidth:400},message,undefined,"");
                            }
                        }
                        const newsession_id = jQuery("#sessionselector").val();
                        rostersession.setid(newsession_id);
                        reset();
                        clearattendees();
                        jsclientsessions.forEach(function (cs,cs_idx) {
                            // jsclientsessions[clientsession_id] = [session_id,client_id]
                            // jsclients[client_id] = client_name
                            if (cs[0] == newsession_id) {
                                const clientname= jsclients[cs[1]];
                                const newli = makelistitem(cs_idx,cs[1],clientname)
                                jQuery("#attendeeslist").append(newli); 
                            }
                        });                   
                        setlihandlers(1);
                    }
                }
                function isinthefuture(sessiondatestr){
                    const d = sessiondatestr;
                    const sd = d.slice(6)+"-"+d.slice(3,5)+"-"+d.slice(0,2)+"T00:00:00Z";
                    const sessiondate = new Date(sd);
                    const today = new Date();
                    return (sessiondate > today)
                }
                function reset(){
                    jQuery('#kbdinput').val('');
                    clearmatches();
                }
                async function addattendee (listitem){
                    jQuery("#matchingclients ul li").off();
                    jQuery("#attendeeslist li").off();
                    const session_id = rostersession.getid();
                    const client_id = listitem.data("clientid");
                    const thisdata = session_id+","+client_id;
                    vols.cursor.wait();
                    // jQuery("#kbdmsg").html("Adding to Session:  "+thisdata+"<BR>"); 
                    const result = await doServerRequest('',thisdata,'addclientsession');
                    // jQuery("#kbdmsg").html(jQuery("#kbdmsg").html() + result + "<BR>"); 
                    vols.cursor.default();
                    if (result.startsWith("OK:")) {
                        const clientsession_id = result.slice(3);
                        populateclientsessions([clientsession_id,session_id,client_id]);
                        jQuery("#attendeeslist").append(listitem.prop("outerHTML")); 
                        jQuery("#attendeeslist li").last().data("clientsessionid",clientsession_id);
                        const test = jQuery("#attendeeslist li").last().data("clientsessionid");
                        listitem.remove();
                        reset();
                        checkmondayattendance(client_id);
                    }
                    setlihandlers(1);
                }
                async function checkmondayattendance(client_id) {
                    const $opt = jQuery('#sessionselector option:selected');
                    const datestr = $opt.data('date'); // 'dd.mm.yyyy'
                    if (!datestr) return;
                    try {
                        const result = await doServerRequest('', JSON.stringify({client_id: client_id, session_date: datestr}), 'attendance_checkmondayattendance');
                        const r = JSON.parse(result);
                        const showMonday = r.attended;
                        if (r.needs_reregistration) {
                            const cb = showMonday
                                ? function() { jQuery.volsdialog('OKMSG', 'This client also attended on Monday this week.', undefined, undefined, 'Also attended Monday'); }
                                : undefined;
                            jQuery.volsdialog('OKMSG', 'This client\'s registration is more than 12 months old and needs to be renewed.', cb, undefined, 'Re-registration Required');
                        } else if (showMonday) {
                            jQuery.volsdialog('OKMSG', 'This client also attended on Monday this week.', undefined, undefined, 'Also attended Monday');
                        }
                    } catch(e) { console.error('checkmondayattendance', e); }
                }
                function removeattendee(listitem) {
                    const content = "Remove this attendee?"
                    const OKparam = listitem;
                    if (checkdeletions) {
                        jQuery.volsdialog("YESNO", content,completetheremoval, undefined, "Please confirm...",{},{minWidth:400},OKparam,undefined);
                    } else {
                        completetheremoval(listitem);          
                    }
                }
                const completetheremoval = async function (listitem) {
                    try { 
                        const clientsession_id = listitem.data("clientsessionid");
                        vols.cursor.wait();
                // jQuery("#kbdmsg").html("Deleting Client:  "+clientsession_id+"<BR>"); 
                        const result = await doServerRequest(clientsession_id,'','deleteclientsession');
                        vols.cursor.default();
                        if (result == "OK") { 
                            delete jsclientsessions[clientsession_id];
                            listitem.remove();
                            findmatches();
                        } else {
                            console.log(result)
                        }
                    } catch(error) {
                        jQuery.volsdialog("OKMSG", error, undefined, undefined, "Sorry, but something went wrong...");
                    }
                }
                function findmatches() {
                    clearmatches();
                    const searchterm = jQuery('#kbdinput').val();
                    if (searchterm !== "") {
                        jsclients.forEach((jsclient,jsclient_id) => {
                            if (jsclient.toUpperCase().indexOf(searchterm) === 0 && jQuery("#attendeeslist li[data-clientid="+jsclient_id+"]").length === 0) {
                                const newli = makelistitem('',jsclient_id,jsclient)
                                jQuery("#matcheslist").append(newli);
                                setlihandlers(1);
                            }
                        })
                    }
                }
                function clearmatches() {
                    jQuery("#matcheslist").empty();
                }
                function clearattendees() {
                    jQuery("#attendeeslist").empty();
                }
                function findtodayssession() {
                // jQuery("#kbdmsg").html(jQuery("#sessionselector-1434").data("date") + "<BR>"+jQuery("#sessionselector-1434").text + "<BR>")    
                    const selectall = "#sessionselector option";
                    jQuery(selectall).prop('selected', false);
                    const todaystr = jQuery.datepicker.formatDate('dd M yy', new Date()); //dd.mm.yy
                    const selector = "#sessionselector option:contains('"+todaystr+"')";
                    if (jQuery(selector).length) {
                        // needed the following approach to get the ipad to show the selection
                        jQuery("#sessionselector").val(jQuery(selector).val());
                    } else if (!jQuery("#sessionselector").hasClass("hidden")) {
                        const todaystr = jQuery.datepicker.formatDate('M yy', new Date());
                        const selector = "#sessionselector option:contains('"+todaystr+"')";
                        jQuery(selector).first().attr("selected","selected");
                    }
                } 
                function enterFullscreen(element) {
                    if (element.requestFullscreen) {
                        element.requestFullscreen();
                    } else if (element.mozRequestFullScreen) { // Firefox
                        element.mozRequestFullScreen();
                    } else if (element.webkitRequestFullscreen) { // Chrome, Safari and Opera
                        element.webkitRequestFullscreen();
                    } else if (element.msRequestFullscreen) { // IE/Edge
                        element.msRequestFullscreen();
                    }
                }
                function exitFullscreen() {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.mozCancelFullScreen) { // Firefox
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) { // Chrome, Safari and Opera
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) { // IE/Edge
                        document.msExitFullscreen();
                    }
                }
            </script>
        JS; 
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $script;
    }



}