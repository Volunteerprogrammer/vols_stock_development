<?php
namespace app\view\form;
use \lib\StdLib as lib;
class RosterForm extends \fw\view\form\Form {
    private $trace= false;
    private $ajaxurl = "https://vols.woodendnh.org.au/staging/";
    private $config;  
    private $requestdata;  
    private $userdata;  
    private $active_user;  
    private $roledata;  
    private $roleids;  
    private $page_id;  
    private $sessiondata;  
    private $bookingdata;  
    private $firstdatestr;  
    private $pagedepth;  
    private $pagename;  
    private $names;  
    private $menu;  
    private $pattern = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
    private $replacement = ["\t[","ray","\t)","="];
    protected $page;              
    public  function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function init($sessn){
        parent::init($sessn);
        $this->requestdata = $this->session->getrequestdata();
        $this->config = $this->session->getconfig();
        $this->active_user = array_key_exists("active_user",$this->requestdata) ? $this->requestdata["active_user"]: $this->session->getuserid();
     }
    public  function setdata($userdata,$roledata,$page_id,$sessiondata,$bookingdata,$firstdatestr,$pagenum,$pagedepth,$pagename,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->userdata = $userdata;
        $this->roledata = $roledata;
        $this->roleids = count($this->roledata)==0?"":implode(",",array_column($this->roledata, 'id'));
        // lib::pr($this->roleids);
        $this->page_id = $page_id;
        $this->sessiondata = $sessiondata;
        $this->bookingdata = $bookingdata;
        $this->firstdatestr = $firstdatestr;
        $this->pagedepth = $pagedepth;
        $this->pagenum = $pagenum;
        $this->pagename = $pagename;
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public  function setadmindata($names,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->names = $names;
        $this->active_user = isset($this->active_user)?$this->active_user : 0;
        // lib::v(__METHOD__." this->active_user = 0 ");
        // lib::v(__METHOD__,$this->names);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }    
    public  function setrequired() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->required = array("loginname"=>"Login Name","password"=>"Password");
     }
    public  function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("loginname"=>"","password"=>"");
        }
    protected function renderproblemsheader($trace=false) {
        if ($this->trace|| $trace) { echo "Enter ".__METHOD__." Missing fields = ";var_dump($this->missingfields);echo  "<br>\n"; }
        $errors = '';
        $loginerror = $this->session->getloginerrormessage();
        if (count($this->missingfields) || strlen($loginerror)) {
            $errors = '<div class="errorbox">'."\n";
            $errors.= '<div class="errorheading"><p>There\'s a problem with your login.</p>'."\n".'</div><!-- errorheading -->'."\n";
            if (strlen($this->loginerror)) {
                $errors.= '<div class="errorbody"><p>'.$loginerror.'</p>'."\n".'</div><!-- errorbody -->'."\n";
            }
            if (count($this->missingfields)) {
                $fcount = 0;
                $errors.= '<div class="errorheading">You must enter :</div><div class="errorbody"><p>';
                foreach ($this->missingfields as $val){
                    if ($fcount != 0) {$form .= ', ';}
                    ++$fcount;
                    $errors.= $val;
                }
                $errors.= "</p>\n</div><!-- errorbody -->\n";
            }
            $errors.= "</div><!-- errorbox -->\n";
        }
        return $errors;
     }
    public  function render($p="",$x="",$xx="",$rights=[],$isadmin=false,$menu='',$trace="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->isadmin = $isadmin;
        $buttons = $this->buildheading($rights,$menu,$trace);
        $content =<<<ROSTER
        <div id="#bookinghistorydialog" style="display:none;"><p>THIS IS THE DEFAULT CONTENTS</p></div>
        <div id ="sessiongridcontainer" class="floating">
            <div id=buttoncontainer>{$buttons}</div>
        ROSTER;
        $tasks = $this->buildsessions($rights,$cellcount);
        if (is_array($tasks)) {
            $content .= "<div id=rostercontainer>";        
            $columns = 0;
            $bgcolor = 1;
            $curgroup = "";
            foreach ($tasks as $taskname => $task) {
                if ($task["taskgroup"]<> $curgroup) {
                    if ($curgroup <> "") {
                        $content .= "</div> <!-- group -->";
                    }
                    $content .= "<div class='taskgroupcontainer  bg".$bgcolor++."'>";
                    $curgroup = $task["taskgroup"];
                }
                $heading  = '<div class="taskheading">'.$taskname.'</div>';
                $content .= '<div id="'.$taskname.'" class="taskcontainer cellsperrow'.$task["cellsperrow"].'">'.$heading.$task["sessions"].'</div><!-- taskcontainer -->';
            } 
            if ($curgroup !== "") {
                $content .= "</div> <!-- taskgroupcontainer -->";
            }
            $content  .="</div> <!-- rostercontainer -->";        
        }
        $content  .="</div> <!-- sessiongridcontainer -->";
        // $content  .="</div><!-- ??? -->";
        $formscript = $this->buildformscript($rights);
        $content  .= $this->buildforms($formscript,$rights);
        $content  .= $this->jsreportfunction();
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;  
     }
    // =========== START rendering header section ====================================
    private function buildheading ($rights,$menu,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $pn = $this->pagenum."||";
        $content  = $this->greetingrow($menu,$trace);
        $content  .= '<div class="headeroptions">';
        if ( $this->isadmin || in_array("{$pn}BOOKOTHERS",$rights)) {
            $content .= $this->bookothers($trace);
        } else {
            $content  .= $this->volunteerheader($pn,$rights,$trace);
        }
        if ( $this->isadmin || in_array("{$pn}EXTEND",$rights)) {
            $content .= $this->extendroster($trace);
        } 
        if ( $this->isadmin || in_array("{$pn}CHANGEDATE",$rights) || in_array("{$pn}CHANGEDEPTH",$rights) || in_array("{$pn}PRINT",$rights)) {
            $content .= $this->datedepthrow($pn,$rights,$trace); 
        }
        if ( $this->isadmin || in_array("{$pn}PUBLISH",$rights)) {
            $content .= $this->publishrow($pn,$rights,$trace);
        }
        $content  .= '</div>';
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
        RETURN $content;
     }
    private function greetingrow ($menu,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        //Welcome {$this->session->getgreeting()}
        $content =  <<<BUTTONS
                <div class="greetingcontainer">
                    <div id="refreshbutton" class="clickable menu nouppercase">Refresh</div>
                    <div class="greeting" >$this->pagename</div> 
                    {$menu}
                </div>
            BUTTONS;
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function bookothers ($trace){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $selectoptions = '';
        $userselect = $this->component->renderdropdown("recordselector",1,$selectoptions,true,false,false,false,$this->names,$this->active_user,false,'vols-form-select','',$trace);
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
        return "<div class='rosteroption'>Manage Bookings for {$userselect}</div>";
     }
    private function extendroster($trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $startdate = $this->component->renderdateinput("startdateinput","date","","","",1,false,"","","",false,false,false);
        $untildate = $this->component->renderdateinput("untildateinput","date","","","",1,false,"","","",false,false,false);
        $content   =<<<BUTTONS
                        <div class="rosteroption">
                            <div id="extend" class="clickable action doitbg " >Build Roster</div>  
                            <span>from</span> {$startdate} <span>until</span> {$untildate} </div>
                    BUTTONS;
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
       return $content;
     }
    private function datedepthrow($pn,$rights,$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content = '<div id="rosternav" class="rosteroption" >';
        if ( $this->isadmin || in_array("{$pn}CHANGEDATE",$rights)) {
            

            $content .= <<<BUTTONS
                        <div id="navcontainer">
                        <div id="tofirst"      class="navdirectionbtn clickable action  " data-direction="-2">{$this->component->geticon("doubleleft")}</div>  
                        <div id="backapage"    class="navdirectionbtn clickable action  " data-direction="-1">{$this->component->geticon("left")}</div>  
                        <div id="resetpage"    class="navdirectionbtn clickable action  " data-direction="0">{$this->component->geticon("today")}</div>  
                        <div id="forwardapage" class="navdirectionbtn clickable action  " data-direction="1">{$this->component->geticon("right")}</div>  
                        <div id="tolast"       class="navdirectionbtn clickable action  " data-direction="2">{$this->component->geticon("doubleright")}</div>
                        </div>
            BUTTONS;        
        }
        if ($this->isadmin || in_array("{$pn}CHANGEDEPTH",$rights)) {
            $depths = [1,4,6,8,10,12,24,48];
            $selectoptions = '';
            $pagedepthselector = $this->component->renderdropdown("pagedepthselector",1,$selectoptions,false,false,false,false,$depths,$this->pagedepth,true,'vols-form-select','',$trace);
            $content .= "<div id='pagedepthcontainer' >Rows per page {$pagedepthselector}</div>";
        }
        if ( $this->isadmin || in_array("{$pn}PRINT",$rights)) {
            $content .= "<div id='printbuttoncontainer'><div id='printbutton' class='clickable action' >{$this->component->geticon("printer")}</div></div>";
        }
        $content .= "</div>";
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    private function publishrow($pn,$rights,$trace=false) {
       if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content = '<div  id="updatediv">';
        $content .= '<div id="updatepublication" class="clickable action doitbg " >Update Publication changes</div>';
        $content .= '</div>';
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
       return $content;
     }
    private function volunteerheader ($pn,$rights,$trace=false){
       if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $content  = '<ul class="buttonlist">';
        if ( $this->isadmin || in_array("{$pn}BOOK",$rights)) {
            $content .='<li>Click a <div class="clickable cellavailable noclick" style="display:inline-block">Green block</div> to MAKE a new booking</li>';
        }
        if ($this->isadmin || in_array("{$pn}CANCEL",$rights)) {
            $content .='<li>Click <div class="clickable cellisyou noclick" style="display:inline-block">your name</div> to CANCEL your booking</li>';
        }
        $content .="</li></ul>";
        $content  .='<div id="updatediv" class="displayed"><span class="logouttext">Please <div class="clickable logout" style="display:inline-block">LOGOUT</div> or choose a menu option when finished.</span></div>';
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $content;
     }
    // ======== END rendering header section ==== START rendering body section ===========
    private function buildbookings ($sessionid,$sessionroles,$inthepast,$istoday,$canceltodayrights,$accesspastsessions,$sessionpublished,$trace=FALSE) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        // $sessionroles is an array of arrays containing 4 items of data per session role:
        // [0]=rolename[1]=sessionrole_id[2]=min_bookings[3]=maxbookings,[4]=role_id,[5]=cellname. 
        // For each sessionrole we create existing bookings then empty bookings, up to 'max'  bookings 
        $class = "";
        $bookings = "";
        $youpresent = 0;
        $index =  1;  //index 1, 5, etc contain the session_role_id 
        // lib::pr($sessionroles);
        foreach ($sessionroles as $sessionrole) { 
            // so this loop deals with a given session/role combination
             // r.name,sr.id,sr.min_quantity,sr.max_quantity,r.id,r.cellname
            $session_role_id = $sessionrole[1];
            $max_bookings = $sessionrole[3];
            $role_id = $sessionrole[4];
            $cellname = $sessionrole[5];
            $bcount = 0;
        // lib::pr($sessionrole);
            foreach ($this->bookingdata as $booking) {
                if ($booking["session_role_id"] == $session_role_id) {
                    $id = $booking['booking_id'];
                    $roleid = $booking['booking_id'];
                    $bookings .= "<div id='{$id}' "; // may need to cancel
                    $class = "";
                    if ($booking["user_id"] === $this->active_user) {
                        $class .= (($inthepast && !$accesspastsessions) || ($istoday && !$canceltodayrights)) ? " celloccupied": " cellisyou";
                        $youpresent = 1;
                    } else {
                        $class .= " celloccupied";
                    }
                    $role_id = $booking["role_id"];
                    $bookings .= " class='clickable volcell no-select {$class}'";
                    $bookings .= " data-user='{$booking["user_id"]}' ";
                    $bookings .= " data-role='{$booking["role_id"]}'"; // check if needed??
                    $bookings .= " data-sessionrole='{$booking["session_role_id"]}'>";
                    $name = ($booking["display_name"]!==""?$booking["display_name"]:
                                ($booking["given_name"]!==""?$booking["given_name"]:
                                    $booking["family_name"]));
                    $bookings .= $name."</div>";
                    $bcount++;
                }
            } 
         // lib::pr($sessionrole,$bcount);
            if ($bcount < $max_bookings) {
                while ($bcount++ < $max_bookings) {
                    $bookings .= "<div data-role='{$role_id}' ";
                    $bookings .=     " data-sessionrole='{$session_role_id}'";
                    $occupiedclass = (($youpresent || ($inthepast && !$accesspastsessions)) ? "celloccupied":"cellavailable");
                    $cname = $youpresent?"":$cellname; // don't show role if user is already in the session
                    if ($sessionpublished) {
                        $bookings .= " class='clickable volcell ".$occupiedclass."'>{$cname}</div>";
                    } else  {
                        $bookings .= " class='bookingcell volcell  clickable unpublished'>Unpublished</div>";
                    }
                }
            }    
        }
        return $bookings;
     }
    private function buildsessions ($rights,&$maxcellcount){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $curgroup = "";
        $curgroupindex = "";
        $curtask = "";
        $en = "";
        $prevmonth = ""; 
        $tasks = [];
        $accesspastsessions = $this->isadmin || in_array($this->pagenum."||OLDSESSIONS",$rights);  
        $prevdow = 7;
        $canceltodayrights = $this->isadmin || in_array($this->pagenum."||CANCELONTHEDAY",$rights);  
        // $this->sessiondata contains [task_id,task_name,session_id,session_start,session_finish,roles=[[rolename|sr.id|sr.min_quantity|sr.max_quantity]]]
        // one record per session
        // lib::v($this->sessiondata);
        if (is_array($this->sessiondata)) {
            $today = new \DateTime();
            // lib::pr($this->sessiondata);
            foreach ($this->sessiondata as $session) {
                $groupchange = ($session["taskgroup"] !== $curgroup);
                if ($groupchange) {
                    $curgroup = $session["taskgroup"];

                }
                if ($groupchange || ($session["groupindex"] !== $curgroupindex)) {
                    $curgroupindex = $session["groupindex"];
                    $curtask = $session["task_name"];
                    $tasks[$curtask] = ["sessions"=>"","taskgroup"=>$session["taskgroup"],"groupindex"=>$session["groupindex"],"cellsperrow"=>$session["cellsperrow"],"sessiondepth"=>$session["sessiondepth"]];
                    $iseven = 0;
                    $bgcolclass = "odd";
                    $prevdow = 7;
                }
                // lib::pr($curtask."||".$tasks[$curtask]["taskgroup"]."~".$tasks[$curtask]["groupindex"]."~".$session["session_start"]);                    
                //=======================
                $sessionroles = explode("~~",$session["roles"]); 
                foreach($sessionroles as &$role) {
                    $role = explode("|",$role);
                }
                $cellcount = array_sum(array_column($sessionroles,3));
                $maxcellcount = max($maxcellcount,$cellcount);
                //======================
                $weekdays = lib::NumberOfSetBits($session["weeklydow"]);
                //======================
                // format the $date with/without dayname depending on recurrence within a week
                $date = date(($weekdays>1?"D ":"").'j M', strtotime($session["session_start"]));
                $sessiondate = new \DateTime($session["session_start"]);
                $inthepast = ($today > $sessiondate);
                $istoday = ($today->format("Ymd") == $sessiondate->format("Ymd"));
                if ($weekdays == 1){
                    $thismonth=date('M', strtotime($session["session_start"]));
                    if ($thismonth !== $prevmonth) {
                        $iseven = ($iseven+1) % 2;
                        $prevmonth = $thismonth;
                        $bgcolclass = $iseven ? "even" : "odd" ; // alternate these
                    }
                } else {
                    $thisdow=date('w', strtotime($session["session_start"]));
                    if ($thisdow <= $prevdow) { // rolls over ever week
                        $iseven = ($iseven+1) % 2;
                        $prevdow = $thisdow;
                        $bgcolclass = $iseven ? "even" : "odd" ; // alternate these
                    }
                }
                // now build the HTML for one session
                $tasks[$curtask]["sessions"] .= "<div class='sessioncontainer {$bgcolclass} ".($session["session_is_holiday"]?" isholiday ":"")."'";
                $tasks[$curtask]["sessions"] .= " data-start='{$session["session_start"]}' data-finish='{$session["session_finish"]}'  >";
                $tasks[$curtask]["sessions"] .= '<div id="session'.$session["session_id"].'" class="sessiondate">'; // 
                $tasks[$curtask]["sessions"] .= '<div class="sessiondatetext';
                $tasks[$curtask]["sessions"] .= (!$inthepast || $accesspastsessions)?"":" inthepast"; 
                if ( $this->isadmin || in_array("{$this->pagenum}||PUBLISH",$rights)) {
                    $tasks[$curtask]["sessions"] .= ' clickable'.($session["published"]?"":" notpublished").'"';
                    $tasks[$curtask]["sessions"] .= ' data-published="'.($session["published"]?"1":"0").'" data-changed="0"';
                } else {
                    $tasks[$curtask]["sessions"] .= '"';
                }   
                $tasks[$curtask]["sessions"] .= '>'.$date."</div>";
                if ( $this->isadmin || in_array("{$this->pagenum}||BOOKINGHISTORY",$rights)) {
                    $tasks[$curtask]["sessions"] .= "<div title='View Booking History' class='historyclick'>{$this->component->geticon("bookinghistory")}</div>";
                }
                $tasks[$curtask]["sessions"] .= "</div>";
                if (!$session["session_is_holiday"]) {
                    // accumulate the max number of bookings allowed under all roles
                    // $session["roles"] format e.g.: "Volunteer|2|2|4~~Supervisor|1|1|1~~" 
                    // first convert to 2D array
                    // process the bookings data them fill up to the max with empty cells
                    $tasks[$curtask]["sessions"] .= "<div class='cells ".(!$inthepast || $accesspastsessions?"":" inthepast")."'>".$this->buildbookings($session["session_id"],$sessionroles,$inthepast,$canceltodayrights,$istoday,$accesspastsessions,$session["published"])."</div>"; //!$inthepast || 
                } else {
                    $tasks[$curtask]["sessions"] .= "<div class='cells'><div class='holiday_name'>".$session["session_holiday_name"].' </div></div>';
                }
                $tasks[$curtask]["sessions"] .= "</div>";
            }
        }
        return $tasks;
     }
    private function buildforms ($script,$rights) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $standardinputs = <<<HTML
                    <input type="hidden" name="pp" value="{$this->pagenum}" />
                    <input type="hidden" name="p" value="{$this->pagenum}" />
                    <input type="hidden" name="active_user" value="{$this->active_user}"/>
        HTML;
        $forms = <<<FORMS
            <div id="formcontainer">
                <form id="menuactionform" method="POST" >
                    <input type="hidden" name="menuactionform" value="1"/>
                    <input type="hidden" name="pp" value="{$this->pagenum}" />
                    <input type="hidden" name="p" />
                    <input type="hidden" name="menuid" value=""/>
                </form>
                <form id="refreshform" method="POST" >
                    {$standardinputs}
                    <input type="hidden" name="formname" value="refreshform"/>
                    <input type="hidden" name="firstdate" value="{$this->firstdatestr}" />
                    <input type="hidden" name="direction" value="" />
                    <input type="hidden" name="pagedepth" value="{$this->pagedepth}" />
                </form>
                <form id="logoutform" method="POST" >
                    {$standardinputs}
                    <input type="hidden" name="p" value="1" />
                    <input type="hidden" name="formname" value="logoutform"/>
                </form>
        FORMS;
        if ( $this->isadmin || in_array("{$this->pagenum}||EXTEND",$rights)) {
            $forms .= <<<FORMS
                    <form id="extendrosterform" method="POST" >
                        {$standardinputs}
                        <input type="hidden" name="formname" value="extendrosterform"/>
                        <input type="hidden" name="page_id" value="{$this->page_id}"/>
                        <input type="hidden" name="startdate" value=""/>
                        <input type="hidden" name="untildate" value=""/>
                    </form>
            FORMS;
        }    
        if ( $this->isadmin || in_array("{$this->pagenum}||CANCEL",$rights) || in_array("{$this->pagenum}||CANCELONTHEDAY",$rights)) {
            $forms .= <<<FORMS
                    <form id="cancelbookingform" method="POST" >
                        {$standardinputs}
                        <input type="hidden" name="formname" value="cancelbookingform"/>
                        <input type="hidden" name="booking_id" value=""/>
                        <input type="hidden" name="deleted_by" value="{$this->session->getuserid()}"/>
                    </form>
            FORMS;
        }    
        if ( $this->isadmin || in_array("{$this->pagenum}||BOOK",$rights)) {
            $forms .= <<<FORMS
                    <form id="makebookingform" method="POST" >
                        {$standardinputs}
                        <input type="hidden" name="formname" value="makebookingform"/>
                        <input type="hidden" name="booked_by" value="{$this->session->getuserid()}"/>
                        <input type="hidden" name="session_role_id" value="">
                    </form>
            FORMS;
        }    
          if ( $this->isadmin || in_array("{$this->pagenum}||PUBLISH",$rights)) {
            $forms .= <<<FORMS
                    <form id="updatepublicationform" method="POST" >
                        {$standardinputs}
                        <input type="hidden" name="formname" value="updatepublicationform"/>
                        <input type="hidden" name="user_id" value="{$this->active_user}"/>
                        <input type="hidden" name="updatedata" value="">
                    </form>
            FORMS;
        }    
        $forms .= "{$script}</div>";
        return $forms;
     }
    // ======== END rendering body section ==== START building scripts  ===========
    private function admin_script() {
        $script = "";
        if ($this->isadmin) {
        }
        return $script;
     }
    private function buildformscript ($rights=[]) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // lib::v($rights);
        $individual = intval (!($this->isadmin || in_array("{$this->pagenum}||BOOKOTHERS",$rights)));
        $script     = "<script>";
        $script     .= <<<JS
                
                    function setwaitcursor(){
                        jQuery("#sessiongridcontainer, #rostercontainer, #buttoncontainer, #sessiongridcontainer #rostercontainer .taskgroupcontainer,.clickable.cellisyou:not(.noclick).waiting, .clickable.cellavailable:not(.noclick).waiting,.clickable.logout.waiting").addClass("waiting");
                        disableclicks();
                    } 
                    function clearwaitcursor(){
                        jQuery("#sessiongridcontainer, #rostercontainer, #buttoncontainer, #sessiongridcontainer #rostercontainer .taskgroupcontainer,.clickable.cellisyou:not(.noclick).waiting, .clickable.cellavailable:not(.noclick).waiting,.clickable.logout.waiting").removeClass("waiting");
                    }
                    async function showbookinghistory(sessrowid) {
                        setwaitcursor();
                        const id=sessrowid.substring(7); /* drop "session"*/
                        const sessdate = jQuery("#"+sessrowid+" .sessiondatetext ").html();
                        const taskname = jQuery("#"+sessrowid).parent().parent().find(".taskheading").html();
                        const plaintitle = "Booking history for "+taskname+" on "+sessdate;
                        const markuptitle = "Booking history for <strong>"+taskname+"</strong> on <strong>"+sessdate+"</strong>";
                        result = await  doServerRequest(id,'','bookinghistory');
                        const onloadfunction = function() {jQuery("#vols_dialog .ui-dialog-title").html(markuptitle);};
                        jQuery.volsdialog("BOOKINGHISTORY",result,undefined,undefined,plaintitle,[],[],"",onloadfunction);
                        clearwaitcursor();
                        setbookinghandlers();
                    }
                    function refresh () {
                        jQuery("#refreshbutton input[name='active_user']").val(jQuery("#recordselector option:selected").val());
                        jQuery("#refreshbutton").trigger("click");
                    }
                    function disableclicks() {
                        jQuery(".clickable.cellisyou, .clickable.cellavailable" ).not('.noclick').off( "click");
                    }
                    function displayactiveuser(active_user) {
                        jQuery("div.cellisyou").each(function(){
                            if (jQuery(this).parents(".cells").length) {
                                jQuery(this).parent().children(".celloccupied:not([data-user])").toggleClass("cellavailable celloccupied");
                                jQuery(this).toggleClass("cellisyou celloccupied"); 
                            }   
                        })
                        if (active_user) {
                            let selector = ".cells:not('.inthepast') > div[data-user='"+active_user+"']";
                            jQuery(selector).each(function(){
                                jQuery(this).parent().children(".cellavailable").toggleClass("cellavailable celloccupied");
                                jQuery(this).toggleClass("celloccupied cellisyou");
                            })
                            // check that the user has permission to access each cell. Mark all cells the user has no rights for as unavailable
                            // first get the roles that user has. If this is an individ logged in, the roles are already in $this->roleids
                            // if the user has bookothers rights, the rights for each 'active user' are in the  user selection list
                            let roles = String("");
                            if ({$individual}) {  // is individual
                                roles = "{$this->roleids}"; 
                            } else {
                                roles = String(jQuery("#recordselector option:selected").data("roles"));
                            }
                            const userroles = roles.split(","); // convert to an array
                            cellselector = ".cells:not('.inthepast') > div";
                            // TEST A check all unccupied  cell's roles against the user's roles
                            jQuery(cellselector+".cellavailable").each(function(){
                                cellrole = jQuery(this).data("role");
                                if (!userroles.includes(String(cellrole))) { 
                                    //active user has no rights to this cell
                                    jQuery(this).toggleClass("cellavailable celloccupied")
                                } 
                            })
                            // TEST B this time check cells with no user but marked as occupied (possibly from a run of TEST A for previous active user without the rights)
                            // these might need to be made available for this new active user  
                            jQuery(cellselector+".celloccupied" ).each(function(){ // all occupied cells
                                if (jQuery(this).data('user') === undefined) {  // no user linked to the cell
                                    cellrole = jQuery(this).data("role");
                                    if (userroles.includes(String(cellrole))) { // this user has the right to access this cell, so make available 
                                        jQuery(this).toggleClass("cellavailable celloccupied")
                                    } 
                                }
                            })
                            // TEST C  finally, for each task that this user is in, find all sessions in other tasks that overlap in datetime with this task
                            // and mark them as unavailable - assumption here is that a volunteer cannot perform two roles simultaneously  
                           // first find this user's bookings
                           jQuery(cellselector+".cellisyou").filter(function (index) {
                                const isuser =  (jQuery(this).data('user') !== undefined) && (jQuery(this).data('user') == active_user);
                                return isuser;
                            }).each(function () {
                                // save the datetime range from the parent serssion
                                const sessstart = $(this).closest(".sessioncontainer").data("start");
                                const sessfinish = $(this).closest(".sessioncontainer").data("finish");
                                // find all sessions on the page that overlap with this booking's datetime
                                jQuery(".sessioncontainer").filter( function (index) {
                                     const coincides = (jQuery(this).data("start") < sessfinish) && (jQuery(this).data("finish") > sessstart);
                                     return  coincides;
                                }).each(function () {
                                    // make all the session's child cells unavailable
                                    jQuery(this).find(" div.cells div.cellavailable").each(function() {
                                        jQuery(this).toggleClass("cellavailable celloccupied")}
                                    );
                                })
                            });
                            jQuery("#refreshform        input[name='active_user']").val(active_user);
                            jQuery("#cancelbookingform  input[name='active_user']").val(active_user);
                            jQuery("#makebookingform    input[name='active_user']").val(active_user);
                            jQuery("#extendrosterform   input[name='active_user']").val(active_user);
                            setbookinghandlers();
                        }
                    }
                    function fw_nowstring(format=0) {
                        function pad(n){return  (n<10 ? '0'+n : n.toString());}
                        let nowstring;
                        const now = new Date();
                        let day   =  pad(now.getDate());
                        let month = pad(now.getMonth()+1);
                        let year  = now.getFullYear().toString();
                        let hour  = pad(now.getHours());
                        let mins  = pad(now.getMinutes());
                        let secs  = pad(now.getSeconds());
                        switch (format) {
                          case 0 : nowstring = day + "-" + month  + "-" + year + "  "+ hour + ":" + mins;break;
                          case 1 : nowstring = year +  month  + day + hour + mins+ secs;break;
                          case 2 : nowstring = year+"-"+month+"-"+day+" "+hour +":"+ mins+":"+secs;break;
                          default: nowstring = "Invalid date format supplied";
                        }
                        return nowstring;
                    }            
            JS;
        if ( $this->isadmin || in_array("{$this->pagenum}||EXTEND",$rights)) {
            $script .= <<<JS

                    function DateisValid(dateStr) {
                        // expecting a datestring in yyyy/mm/dd OR mm/dd/yyyy
                        return !isNaN(new Date(dateStr));
                    }
                    function extendroster(){
                        jQuery("#extendrosterform input:hidden[name='startdate']").val(jQuery("#startdateinput").val());
                        jQuery("#extendrosterform input:hidden[name='untildate']").val(jQuery("#untildateinput").val());
                        jQuery("#extendrosterform").trigger( "submit" );
                    }
                    function extenddatechange() {
                        const from = Date.parse(jQuery("#startdateinput").val());
                        const until = Date.parse(jQuery("#untildateinput").val());
                        if (DateisValid(from) && DateisValid(until)) {
                            const today = Date.now();
                            if (today > from || today > until) {
                                jQuery.volsdialog('OKMSG', 'Please select a date range in the future.', undefined, undefined, 'Validation');
                                jQuery("#extend").addClass("inactive");
                            } else if (until < from ) {
                                jQuery.volsdialog('OKMSG', 'Your start date is after your finish date.', undefined, undefined, 'Validation');
                                jQuery("#extend").addClass("inactive");
                            } else { 
                                jQuery("#extend").removeClass("inactive");
                            }
                        } else {
                            jQuery("#extend").addClass("inactive");
                        }
                    }
            JS;
        }    
        if ( $this->isadmin || in_array("{$this->pagenum}||BOOKOTHERS",$rights)) {
            $script .= <<<JS

                    jQuery("#recordselector").change(function(){
                        let active_user = jQuery("#recordselector option:selected").val();
                        displayactiveuser(active_user);
                    })
            JS;
        }    
        if ( $this->isadmin || in_array("{$this->pagenum}||BOOK",$rights) || in_array("{$this->pagenum}||CANCEL",$rights)) {
            $script .= <<<JS

                    function setbookinghandlers() {
                        jQuery(".clickable.celloccupied").off( "click");
                        jQuery(".clickable.celloccupied[id][data-user]:not(.cellisyou)").css('cursor', 'pointer').off( "dblclick").on("dblclick",function() {
                            const userid = jQuery(this).data("user");
                            jQuery("option#recordselector-"+userid).prop("selected",true).trigger("change");
                        });
                        
            JS;
            if ( $this->isadmin || in_array("{$this->pagenum}||BOOK",$rights)) {
                $script .= <<<JS

                        jQuery(".clickable.cellavailable" ).not('.noclick').off( "click").on( "click", function(event) {
                            disableclicks();
                            if (jQuery("#recordselector").length) {
                                jQuery("#makebookingform input[name='user_id']").val(jQuery("#recordselector option:selected").val());
                            } else {
                                jQuery("#makebookingform input[name='user_id']").val(jQuery("#recordselector option:selected").val());
                            }
                            jQuery("#makebookingform input[name='session_role_id']").val(jQuery(this).data("sessionrole"));
                            jQuery("#makebookingform").trigger( "submit" );
                        });
                JS;
            }
            if ( $this->isadmin || in_array("{$this->pagenum}||CANCEL",$rights)) {
                $script .= <<<JS

                        jQuery(".clickable.cellisyou" ).not('.noclick').off( "click").on( "click", function(event) {
                            setwaitcursor();
                            jQuery("#cancelbookingform input[name='booking_id']").val(jQuery(this).attr('id'));
                            jQuery("#cancelbookingform").trigger( "submit" );
                        }); 
                JS;
            }    
            $script .= '    }';
        }
        // $script .= $this->ajaxscript();
        // $script .= $this->dialogscript();
        $script .= $this->onloadscript($rights);
        $script .= '</script>';
        return $script;
     }
    private function onloadscript($rights=[]) {
       // START ONLOAD SCRIPT        
        $script = <<<JS

                // START on load =========================================================================
                jQuery(function(){
                    jQuery("#rostercontainer .sessioncontainer .historyclick").on('click', function(event) {
                        setwaitcursor();
                        showbookinghistory(jQuery(this).parent().prop("id"));
                    });
                    jQuery("#makebookingform input[name='active_user']").val({$this->active_user});
                    jQuery(".clickable.logout" ).on("click", function(event) {
                        setwaitcursor();
                        jQuery("#logoutform").trigger( "submit" );
                    }); 
                    jQuery("#refreshbutton" ) .on("click",function(event) {
                        setwaitcursor();
                        jQuery("#refreshform").trigger( "submit" );
                    }); 
                    setbookinghandlers();
            JS;

        if ( $this->isadmin || in_array("{$this->pagenum}||CHANGEDATE",$rights)) {
            $script .= <<<JS

                    jQuery("#rosternav .navdirectionbtn.clickable" ).on("click",function() {
                        setwaitcursor();
                        jQuery("#refreshform input:hidden[name='direction']").val($(this).data('direction'));
                        jQuery("#refreshform").trigger( "submit" );
                    });

            JS;
        }       
        if ( $this->isadmin || in_array("{$this->pagenum}||CHANGEDEPTH",$rights)) {
            $script .= <<<JS

                    jQuery("#rosternav #pagedepthselector" ).on("change",function() {
                        jQuery("#refreshform input:hidden[name='pagedepth']").val($(this).find(":selected").text());
                        jQuery("#refreshform").trigger( "submit" );
                    });

            JS;
        }
        if ( $this->isadmin || in_array("{$this->pagenum}||PUBLISH",$rights)) {
            $script .= <<<JS

                    // accumulate changes in the sessiondates
                    jQuery("#rostercontainer .sessioncontainer .sessiondatetext" ).on("click",function() {
                        let pub = jQuery(this).data("published") == "1" ? "0" : "1";
                        jQuery(this).toggleClass("notpublished");
                        jQuery(this).data("published",pub);
                        jQuery(this).data("changed","1");
                        jQuery("#updatediv").addClass("displayed"); 
                    }); 
                    // post all the changes to the database
                    jQuery("#updatepublication").on("click",function() {
                        let sessions = [] 
                        setwaitcursor();
                        jQuery("#rostercontainer .sessioncontainer .sessiondatetext ").each(function(index){
                            if (jQuery(this).data("changed") =='1') {
                                const id = jQuery(this).parent().prop("id");
                                const published = jQuery(this).data("published");
                                sessions.push([id,published]);
                            }
                            console.log( index + ": "+jQuery(this).data("changed")+" // "+jQuery(this).data("published"))
                        });
                        if (sessions.length) {
                            jQuery("#updatepublicationform input:hidden[name='updatedata']").val(JSON.stringify(sessions));
                            jQuery("#updatepublicationform").trigger( "submit" );
                        } else {
                            clearwaitcursor();
                        }
                    });
            JS;
        }
        if ( $this->isadmin || in_array("{$this->pagenum}||PRINT",$rights)) {
            $script .= <<<JS

                     jQuery("#printbutton").on("click",function() {
                        let eid = 0;
                        let task = [];
                        jQuery("#rostercontainer div.taskcontainer").each(function() {
                            task.push(jQuery(this).find("div.taskheading").html());
                            let sessions = [];
                            jQuery(this).find("div.sessioncontainer").each(function () {
                                sessiondate = jQuery(this).find("div.sessiondatetext").html();
                                let sessiondata=[]
                                sessiondata.push(sessiondate)
                                jQuery(this).find("div.volcell").each(function () {
                                    celltext = jQuery(this).html();
                                    sessiondata.push(celltext);
                                }); 
                                sessions.push(sessiondata);
                            }); 
                            task.push(sessions);
                        });
                        nowstr = fw_nowstring(1);
                        footer = "Printed at "+fw_nowstring(0);
                        // console.log(task);
                        Print2TaskRoster('ROSTER_'+nowstr,"{$this->config["app"]["DEPARTMENT"]} Roster",task,"{$this->config["app"]["ORGANISATIONNAME"]}",footer);
                    });
            JS;
        }
        if ( $this->isadmin || in_array("{$this->pagenum}||EXTEND",$rights)) {
            $script .= <<<JS

                    jQuery("#startdateinput").on("change",function () {extenddatechange();})
                    jQuery("#untildateinput").on("change",function () {extenddatechange();})
                    jQuery("#extend").on("click",function () {
                        setwaitcursor();
                        extendroster();
                    });
                JS;
        }    
        if ( $this->isadmin || in_array("{$this->pagenum}||BOOKOTHERS",$rights)) {
            $script .= <<<JS

                    jQuery("#recordselector").trigger("change");
            JS;     
        }
            // this is an individual volunteer login
            // user has no "bookothers" rights
        $script .= <<<JS

                if (jQuery("#recordselector").length == 0) { 
                    displayactiveuser({$this->active_user});
                }
        JS;
        // END on load =========================================================================
        $script .= "})";
        return $script;
     } 
    public  function formscript() {
        $script = <<<SCRIPT
                  SCRIPT;             
        return $script;
     }
    public  function jsreportfunction() {
        $jsfunction  = '<script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js"></script>';
        $jsfunction .= '<script src="https://unpkg.com/jspdf-autotable"></script>';
        $jsfunction .= '<script>';
        $jsfunction .= <<<JS
            
            function Print2TaskRoster(reportfilename,reporttitle,tasks,header,footer){
                window.jsPDF = window.jspdf.jsPDF;
                window.autoTable = window.jspdf.autoTable;
                const leftmargin = 15;
                let styles = {overflow: 'linebreak'};
                let headfootstyles = {halign: 'center', fillColor: 200, fontStyle:'bold' };
                let columnstyles = { };
                if (columnstyles === {}) { // no styles provided
                    styles.cellWidth = 'auto';
                }
                let head = [[tasks[0],tasks[2]]];
                let body = [["",""]];
                let foot = [["",""]];
                let pdfdoc = new jsPDF('p');
                pdfdoc.setFontSize(7);
                pdfdoc.setTextColor(0);
                pdfdoc.autoTable({
                    headStyles: headfootstyles,
                    // footStyles: headfootstyles,
                    bodyStyles: {valign: 'top' },
                    columnStyles: columnstyles,
                    rowPageBreak: 'auto',
                    theme: 'plain',
                    minCellHeight: 400,
                    startY: leftmargin,
                    head: head,
                    body: body,
                    foot: foot,
                    styles: styles,
                    didParseCell: (data) => {  //(data.column.index === 0)  &&
                        if (data.section === 'head' || data.section === 'foot') {
                            data.cell.styles.halign = 'left';
                        }
                    },
                    didDrawCell: function (data) {
                        if (data.cell.section === 'body') {
                            if ((data.column.dataKey === 0) || (data.column.dataKey === 1)) {
                                const idx = data.column.dataKey === 0 ? 1:3;
                                const pageSize = pdfdoc.internal.pageSize;
                                const pageWidth = pageSize.getWidth();
                                pdfdoc.autoTable({
                                    body: tasks[idx],
                                    startY: data.cell.y + 2,
                                    margin: {left: data.cell.x },
                                    halign: 'center',
                                    valign: 'middle',
                                    tableWidth:  (pageWidth/2) - 20,
                                    theme: 'grid',
                                    alternateRowStyles: {fillColor: 240},
                                    styles: {
                                        cellWidth: 'auto',
                                        fontSize: 7,
                                        cellPadding: 1,
                                        overflow: 'linebreak'
                                    }
                                });
                            }
                        }   
                    },
                    didDrawPage: function (data) {
                        // Header
                        pdfdoc.setFontSize(14);
                        pdfdoc.text(header+":    "+reporttitle,leftmargin,10);//
                        // Footer
                        // Total page number plugin only available in jspdf v1.0+
                        //  if (typeof pdfdoc.putTotalPages === 'function') {
                        //      str = str + ' of ' + totalPagesExp; //pdfdoc.putTotalPages(); //totalPagesExp
                        //    }
                        const pageSize = pdfdoc.internal.pageSize;
                        const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
                        const pageWidth = pageSize.getWidth();
                        pdfdoc.setDrawColor(0, 0, 0);
                        pdfdoc.line(leftmargin,pageHeight - 14,leftmargin + pageWidth - 35,pageHeight - 14)
                        pdfdoc.setFontSize(7);
                        pdfdoc.text('Page ' + pdfdoc.internal.getNumberOfPages(),leftmargin, pageHeight - 10);
                        pdfdoc.text(footer,leftmargin + pageWidth - 70, pageHeight - 10);
                    }
                });
                pdfdoc.save(reportfilename); //
             }
            
        JS;
        $jsfunction .="</script>";
        return $jsfunction;
     }
}
