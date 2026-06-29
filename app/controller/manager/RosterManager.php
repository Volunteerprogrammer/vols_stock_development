<?php
namespace app\controller\manager;
use \Datetime;
use app\controller\manager\TaskExtenderManager as eem;
use \lib\StdLib as lib;
class RosterManager 
{   // this manager is is responsible for building the dataset required for a roster page,
    // and processing the various forms that might be submitted from the page  
    private $trace=false;
    private $page_id;
    private $firstdate;
    private $session;
    private $db;
    private $config;
    private $emailmanager;
    private $rosterpagerows=5;
    public function __construct(protected \apptable\SessionTable $sessiontable,
                                protected \apptable\TaskTable $tasktable,
                                protected \apptable\PageTable $pagetable,
                                protected \apptable\RosterTable $rostertable,
                                protected \apptable\TaskRoleTable $taskroletable,
                                protected \apptable\SessionRoleTable $sessionroletable,
                                protected \apptable\BookingTable $bookingtable,
                                protected \apptable\UserTable $usertable,
                                protected \app\controller\manager\SessionManager $sessionmanager,
                                protected \app\controller\manager\TaskExtenderManager $taskextendermanager,
                                protected \fw\exception\ErrorHandler $errorhandler
                            ){
       if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function init($session){
        $this->session = $session;
        $this->sessionmanager->init($this->session);
        $this->taskextendermanager ->init($this->session);
        $this->db = $this->session->getdb();
        $this->config = $this->session->getconfig();
        $this->rosterpagerows =  $this->config["app"]["ROSTERPAGEROWS"];
        $this->errorhandler->init($this->config);
        $this->inittables();
       if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    private function inittables(){
        $this->sessiontable->init($this->db);
        $this->tasktable->init($this->db);
        $this->pagetable->init($this->db);
        $this->rostertable->init($this->db);
        $this->taskroletable->init($this->db);
        $this->sessionroletable->init($this->db);
        $this->bookingtable->init($this->db);
        $this->usertable->init($this->db);
     }
    public function initemails($emailmanager){
        $this->emailmanager = $emailmanager;
        $this->emailmanager->init($this->session);
     }
    private function sendbookingemail($action,$user_id,$session_id,&$response,$officenotice=false,$trace=false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $success=$this->usertable->selectonID($user_id,$user,$numusers,false,$trace);
        $success= $success && $this->sessiontable->selectonID($session_id,$session,$numsessions,false,$trace);
        if ($success && $user && $session) {
            // srcset='https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_198,h_187,al_c,q_85,usm_0.66_1.00_0.01,enc_auto/wnh_round_logo_col.png'
            $sessdate = new DateTime($session["start"]); 
            $date = strtoupper($sessdate->format('l, jS F Y'));
            if ($action=="Booking") {
                if ($officenotice) {
                    $textmessage = "{$user['given_name']} {$user['family_name']} has been booked for the Food Bank on {$date}.";
                } else {
                    $textmessage = "You have been booked as a volunteer for the Food Bank on {$date}.";
                }
            } else if ($action=="Cancel")  {   
                if ($officenotice) {
                    $textmessage = "Booking for {$user['given_name']} {$user['family_name']} on {$date} has been cancelled." ;
                } else {
                    $textmessage = "Your Booking for <strong>{$date}</strong> has been cancelled." ;
                }
            }
            $htmlmessage = "<p>{$textmessage}</p>";
            $heading = $this->emailmanager->getheading();
            $footer = $this->emailmanager->getfooter();
            if ($officenotice) {
                $email["TextPart"]= <<<TEXT
                    {$heading["text"]}
                    PLEASE NOTE,
                    $textmessage
                    TEXT;
                $email["HTMLPart"] = <<<TEXT
                    {$heading["html"]}
                    <h1>PLEASE NOTE</h1>
                    $htmlmessage
                    TEXT;
                $email["To"]      = [['Email' =>$this->config["app"]["RECEPTIONEMAIL"],"Name" => $this->config["app"]["RECEPTION"]]];
                $email["Subject"] = "Food Bank LATE CHANGE NOTICE";
                $this->emailmanager->sendmail($email,$user_id,$session_id,$response,$this->errorhandler,$trace);
            } else {
                $sessfinish = new DateTime($session["finish"]);
                $siteUrl   = $this->config["app"]["SITEURL"]   ?? '';
                $siteTitle = $this->config["app"]["SITETITLE"] ?? '';
                $email["TextPart"]= <<<TEXT
                    {$heading["text"]}
                    Dear {$user['given_name']},
                    $textmessage
                    If this is a mistake, you did not do this, or you have any questions, please contact {$this->config["app"]["RECEPTION"]} on {$this->config["app"]["RECEPTIONPHONE"]} ({$this->config["app"]["OFFICEHOURS"]}). Alternatively, to make or cancel your roster bookings, you can login to the Volunteers Roster at {$siteUrl}.               
                    Please remember, Volunteer sessions run from {$sessdate->format('H:i')} to about {$sessfinish->format('H:i')}. This allows for set up before, and cleanup after, the {$this->config["app"]["DEPARTMENT"]} opening times.
                    {$footer["text"]}
                    TEXT;
                $email["HTMLPart"] = <<<TEXT
                    {$heading["html"]}
                    <p>Dear {$user['given_name']},</p>
                    $htmlmessage
                    <p>If this is a mistake, you did not do this, or you have any questions, please contact {$this->config["app"]["RECEPTION"]} on {$this->config["app"]["RECEPTIONPHONE"]} ({$this->config["app"]["OFFICEHOURS"]}). Alternatively, to make or cancel your roster bookings, you can login to the Volunteers Roster at <a href="{$siteUrl}">{$siteTitle}</a>.</p>
                    <p>Please remember, Volunteer sessions run from {$sessdate->format('H:i')} to about {$sessfinish->format('H:i')}. This allows for set up before, and cleanup after, the {$this->config["app"]["DEPARTMENT"]} opening times.</p>
                    {$footer["html"]}
                    TEXT;
                $email["Subject"] = "Booking";
                // $email["To"]      = [['Email' => $user['email'],"Name" => $user['given_name']." ".$user['family_name']]];
                // $email["To"]      = [['Email' => "david@sarum.au","Name" => "DT"]];
                // $this->emailmanager->sendmail($email,$user_id,$session_id,$response,false);
            }   
        }
       if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $success;
     }
// Methods concerned with processing incoming data from a roster page
    public function processdata($trace=false){
       if ($this->trace ) {echo "Enter ".__METHOD__."<br>";}
        $data = $this->session->getrequestdata();
       if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function processcancellation(&$errormessage,&$emailresponse,$trace = false){
       if ($this->trace || $trace ) {echo "Enter ".__METHOD__."<br>";}
        $data = $this->session->getrequestdata();
        $id = $data['booking_id'];
        $matchedrows = 0;
        $emailresponse = "";
        $deletetime = lib::nowstring("Y-m-d H:i:s");
        $set = " `status` = 'deleted', `deleted_time` = '".$deletetime."',`deleted_by` = {$this->session->getuserid()}";
        $success = $this->bookingtable->update($set,"`id`={$id}",$numrows,$errormessage,$trace,$matchedrows,false);
        if ($success) {
            if ($this->bookingtable->selectonID($id,$bookingrecord,$srnumrows,0,$trace)) {
                if ($this->sessionroletable->selectonID($bookingrecord["session_role_id"],$sessionrolerecord,$srnumrows,0,$trace )) {
                    $this->sendbookingemail("Cancel",$bookingrecord["user_id"],$sessionrolerecord["session_id"],$emailresponse);
                    if ($this->sessiontable->selectonID($sessionrolerecord["session_id"],$sessionrecord,$srnumrows,0,$trace )) {
                        $today = new \DateTimeImmutable();
                        $session = new \DateTimeImmutable($sessionrecord["start"]);
                        $interval = $today->diff($session);
                        if ($interval->format("%r%d") <= $this->config["app"]["LATECANCELLATIONPERIOD"]) {
                            $this->sendbookingemail("Cancel",$bookingrecord["user_id"],$sessionrolerecord["session_id"],$emailresponse,TRUE);
                        }
                    }
                }
            }
        }
       if ($this->trace || $trace ) {echo "Leave ".__METHOD__." SET {$set} success={$success}<br>response={$emailresponse}";}
        return $success;
     }
    public function processbooking(&$errormessage,&$emailresponse,$trace= false){
       if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        // lib::v($data);
        $success = false;
        $errormessage = "";
        $sr_id = $data["session_role_id"];
        if ($this->bookingtable->starttransaction(MYSQLI_TRANS_START_READ_WRITE,"processbooking")) {
            // lock the session-role record...
            if ($this->sessionroletable->selectonID($sr_id,$sr_record,$srnumrows,2,$trace)) {
            // lib::v($sr_id,$sr_record,$srnumrows);                
                // ... and check that it the has capacity for another booking.
                $fielddata = array("session_role_id"=>$sr_id,"status"=>"booked");
                if ($this->bookingtable->selectonmultiplefields($fielddata,$brecords,$bnumrows,false,$trace)) {
                    if ($bnumrows < $sr_record["max_quantity"]) {
                        $booked_time = new \DateTime();
                        $booked_time = trim($booked_time->format('Y-m-d H:i:s'));
                        $this->bookingtable->clear();
                        $this->bookingtable->setfield("session_role_id",$sr_id);
                        $this->bookingtable->setfield("user_id",$data["active_user"]);
                        $this->bookingtable->setfield("status","booked");
                        $this->bookingtable->setfield("booked_time",$booked_time);
                        $this->bookingtable->setfield("booked_by",$data["booked_by"]);
                        $success = $this->bookingtable->insert();
                        $this->bookingtable->commit();
                        $this->sendbookingemail("Booking",$data["active_user"],$sr_record["session_id"],$emailresponse);
                        return true;
                    } else {
                        $errormessage = "This session is full ({$bnumrows} bookings present). Sorry, but it looks like someone else just took the last spot.";
                    }
                } else {
                    $errormessage = "Sorry, but there was an error checking whether the session is already full. This is a system problem. We have been informed.";
                }
            } else {
                $errormessage = "Sorry, but there was an error locking the session for insert. Perhaps try again in a few moments.";
            }
        } else {
            $errormessage = "Sorry, but there was an error (starting the database transaction). Perhaps try again in a few moments.";
        }
       if ($this->trace ) {echo "Leave ".__METHOD__."<br>response={$emailresponse}";}
        return true;
     }
// Methods concerned with creating and publishing new roster sessions
    private function doextension($task,$startdate="",$untildate="",$trace=false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        switch ($task["recurrence"]) {
            case "Once-only": $this->extendonceonlytask($task);break;
            case "Daily"    : $this->extenddailytask($task,$startdate,$untildate);break;
            case "Weekly"   : $this->extendweeklytask($task,$startdate,$untildate);break;
            case "Monthly"  : $this->extendmonthlytask($task,$startdate,$untildate);break;
            // case "Yearly"   : $this->yearly($task);break;
            default:
        }
    }
    public  function extendroster($data,&$errormessage,$trace=false){
        // what's used:   $data["page_id"], ["startdate"], ["untildate"]
        // Using the start and until dates supplied in the Request, and the recurrence fields in the tasks,
        // we need to build new sessions and sessionroles to extend the roster
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        // $data = $this->session->getrequestdata();
        // lib::prf(P,R,$data);
        $success = false;
        $page_id = $data["page_id"];
        $errormessage = "";
        $query  = <<<QUERYSTR
                SELECT t.*, ro.leadtime, ro.publishedleadtime, ro.startdate, ro.enddate, ro.sessiondepth
                FROM task t
                JOIN roster ro ON ro.id = t.page_id
                WHERE t.page_id = {$page_id}
                QUERYSTR;
        $success = $this->tasktable->query($query,$tasks, $numrows, false);
        if ($numrows) {
            foreach ($tasks as $task) {
        // lib::pr($task);
                if ($data["startdate"] === "") {
                    $sdt = new \DateTimeImmutable();
        // lib::pr($sdt);
                    $udt = $sdt->modify("+{$task['leadtime']} weeks ");
        // lib::pr($udt);
                    $sd = $sdt->format("Y-m-d"); 
        // lib::pr($sd);
                    $ud = $udt->format("Y-m-d"); 
        // lib::pr($sd);
                } else {
                    $sd = $data["startdate"];
                    $ud = $data["untildate"];
                }
                $startdate = new \DateTimeImmutable ($sd." 00:00:00"); //'yyyy-mm-dd',
        // lib::pr($startdate);
                $untildate = new \DateTimeImmutable ($ud." 00:00:00"); //'yyyy-mm-dd',
        // lib::pr($untildate);
                // $this->extendtask($task,$startdate,$startdow,$untildate,$errormessage);
                // from daemon
                $this->doextension($task,$startdate,$untildate);
            }
        } else {
            $errormessage = "There are no tasks linked to this page.";
        }
       if ($this->trace || $trace ) {echo "Leave ".__METHOD__."  ({$numrows} rows)<br>";}
        return $success;
     }    
    private function extendonceonlytask($task,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $taskday = new \DateTimeImmutable($task["startdate"]);
        return $this->sessionmanager->generatepublish($task,$taskday,$taskday,$this->report,$trace);
     } 
    private function extenddailytask($task,$firstdate,$untildate,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        if (lib::validateDate($task["startdate"],"Y-m-d")) {
            $taskstartdate = new \DateTimeImmutable($task["startdate"]);
            $diff = $this->getdatediff($firstdate,$taskstartdate) ;
            $success = true;
            if ($task["dailyoption"] == 0) { // every x daya from ...
                if ($task["dailyinterval"] > 0) {
                    $period = $task["dailyinterval"];
                    if ($diff < 0) { //then taskstartdate is before firstdate (otherwise just start on $taskstartdate)
                        $mod =  abs($diff) % $period; // may be zero
                        $adddays = $period-$mod;
                        $taskday = $firstdate->modify("+{$adddays} days ");
                    } else  {
                        $taskday =  clone $taskstartdate;
                    }
                    while ($success && ($taskday <= $untildate) ) {
                        $this->sessionmanager->generatepublish($task,$taskday,null,$report,$trace);
                        $taskday = $taskday->modify("+{$period} days ");
                    }
                }
            } else { // weekdays
                $weekdays = "0111110";
                if ($diff < 0) { //then startdate is in the past (otherwise just start on $startdate)
                    $taskday = $firstdate;
                } else  {
                    $taskday = $taskstartdate;
                }
                while ($success && ($taskday <= $untildate) ) {
                    $thisdow =  $this->dowbitstr($taskday,$trace);
                    if (($thisdow & $weekdays) != 0) {
                        $this->sessionmanager->generatepublish($task,$taskday,null,$report,$trace);
                    }
                    $taskday = $taskday->modify("+1 day");
                }
            }
        }
     } 
    private function extendweeklytask($task,$firstdate,$untildate,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__.$firstdate."==".$untildate."<br>".print_r($task,true);} 
        $taskdows = substr("0000000".decbin($task["weeklydow"]),-7);// decbin gives string representation of input so 19 = :"10011"
        $taskdows = strrev($taskdows);
        // $taskdows is in high-bit-first order. We need to reverse that;
        $taskstartdate = new \DateTimeImmutable($task["startdate"]);
        $taskstartdatedow = $taskstartdate->format("w");
        $startofweek = $taskstartdate->modify("-{$taskstartdatedow} days ");
        for ($i=0;$i<=6;$i++) {
            if (substr($taskdows,$i,1) == "1") { // task occurs on this dow
                $taskday = $startofweek->modify("+{$i} days ");
                while ($taskday<$taskstartdate || $taskday<$firstdate)  {
                    $taskday = $taskday->modify("+{$task["weeklyinterval"]} weeks ");
                }
                while ($taskday<=$untildate) {
                    $success = $this->sessionmanager->generatepublish($task,$taskday,null,$report,$trace);
                    $taskday = $taskday->modify("+{$task["weeklyinterval"]} weeks ");
                }
            }
        }
     } 
    private function extendmonthlytask($task,$firstdate,$untildate,$trace=false) {
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $p0 = (($task["monthlyoption"] == 0) && ($task["monthlydayofmonth"] > 0) && ($task["monthlydayofmonth"] < 32) && ($task["monthlyinterval0"] > 0));  
        $p1 = (($task["monthlyoption"] == 1) && ($task["monthlyinterval1"] > 0)); 
        if  ($p0 || $p1) { 
            $taskstartdate = new \DateTimeImmutable($task["startdate"]);
            if ($p0) {
                $taskday = new \DateTimeImmutable("{$taskstartdate->format("Y-m-")}{$task["monthlydayofmonth"]}");
                $interval = $task["monthlyinterval0"];
            } else {
                $taskday = $this->addmonths($taskstartdate,$task["monthlywhichdow"],$task["monthlydow"],0,$trace);
                $interval = $task["monthlyinterval1"];
            }
            while ($taskday<$taskstartdate || $taskday<$firstdate)  {
                if ($p0) {
                    $taskday = new \DateTimeImmutable("{$taskday->modify("+{$interval} months")->format("Y-m-")}{$task["monthlydayofmonth"]}");
                } else {
                    $taskday = $this->addmonths($taskday,$task["monthlywhichdow"],$task["monthlydow"],$interval,$trace);
                }
            }
            while ($taskday<=$untildate) {
                $success = $this->sessionmanager->generatepublish($task,$taskday,null,$report,$trace);
                if ($p0) {
                    $taskday = new \DateTimeImmutable("{$taskday->modify("+{$interval} months")->format("Y-m-")}{$task["monthlydayofmonth"]}");
                } else {
                    $taskday = $this->addmonths($taskday,$task["monthlywhichdow"],$task["monthlydow"],$interval,$trace);
                }
            }
        }
     } 
    private function dowbitstr($taskday,$trace=false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $thisdow = pow(2,(int) $taskday->format("w"));    
        return str_pad(decbin($thisdow),7,"0",STR_PAD_LEFT);        
     }
    private function getdatediff($date1,$date2,$trace=false) {
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
            $diff = $date1->diff($date2) ;
            return (int) $diff->format('%R%a') ; // difference in days
     }
    private function addmonths($taskday,$whichidx,$dowidx,$period,$trace=false) {
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $which = [0=>"first",1=>"second",2=>"third",3=>"fourth",4=>"last"][$whichidx];
        $dayofmonth = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"][$dowidx];
        $newday =  $taskday->modify("+{$period} months");
        return $newday->modify("{$which} {$dayofmonth} of {$newday->format("F Y")}");
     }
    public  function updatepublicationdata(&$errormessage,$trace=false){
        // Update the "published " data for the sessions contained in $REQUEST.updatedata
        // updatedata = [ [session_id,published], [session_id,published],...]
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $rqstdata = $this->session->getrequestdata();
        if (array_key_exists("updatedata",$rqstdata)) {
            $updatedata = json_decode($rqstdata["updatedata"]);
            // lib::pr($rqstdata, $updatedata);            
            foreach ($updatedata as $record) {
                $id = preg_replace("#[a-zA-Z]+#","",$record[0]);
                $success = $this->sessiontable->update("`published` = '".trim($record[1])."'"," `id`='{$id}'",$numrows,$errormessage); 
                // lib::pr("==============",$id,trim($record[1]));
                if (!$success) {
                    break;
                }
            }
        }
       if ($this->trace || $trace ) {echo "Leave ".__METHOD__."  ({$numrows} rows)<br>";}
        return $success;
     }
//  Methods concerned with preparing the data for a roster page
    //     public function getdefaultpage(){
        //         $success = $this->pagetable->selectononefield("is_default","1",$ts,$numrows,false,false);
        //         if ($success && $numrows) {
        //             return $ts[0]["id"];
        //         } else {
        //             return false;
        //         }
        //      }
    private function loadallsessions($page_id,$include_unpublished,&$results,&$numrows,$trace=false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $andonlyifpublished = $include_unpublished?"":" AND sess.published = 1";
        $query = <<<QUERYSTR
            SELECT  ro.id as page_id
                   ,ro.maxcolumns as maxcols
                   ,ro.startdate AS taskstartdate
                   ,ro.sessiondepth AS sessiondepth
                   ,t.id as task_id
                   ,t.name AS task_name
                   ,t.recurrence AS recurrence
                   ,t.dailyoption AS task_recurrence
                   ,t.dailyinterval AS dailyinterval
                   ,t.weeklyinterval AS weeklyinterval
                   ,t.weeklydow AS weeklydow
                   ,t.monthlyoption AS monthlyoption
                   ,t.monthlydayofmonth AS monthlydayofmonth
                   ,t.monthlyinterval0 AS monthlyinterval0
                   ,t.monthlywhichdow AS monthlywhichdow
                   ,t.monthlydow AS monthlydow
                   ,t.monthlyinterval1 AS monthlyinterval1
                   ,t.taskgroup AS taskgroup
                   ,t.groupindex AS groupindex
                   ,t.cellsperrow AS cellsperrow
                   ,sess.id AS session_id
                   ,sess.published AS published
                   ,sess.start AS session_start
                   ,sess.finish AS session_finish
                   ,sess.is_holiday AS session_is_holiday
                   ,sess.holiday_name AS session_holiday_name
                   ,GROUP_CONCAT(CONCAT_WS("|",r.name,sr.id,sr.min_quantity,sr.max_quantity,r.id,r.cellname,r.rosterindex) ORDER BY r.rosterindex  SEPARATOR "~~" ) AS roles
            FROM roster ro
            JOIN task t ON ro.id = t.page_id
            JOIN session sess ON t.id = sess.task_id
            JOIN session_role sr ON sess.id = sr.session_id
            JOIN role r ON r.id = sr.role_id
            WHERE ro.id = {$page_id}
                  {$andonlyifpublished}
            GROUP BY sess.id
            ORDER BY sess.start;
        QUERYSTR;
        $success = $this->tasktable->query($query, $results, $numrows,$trace); 
        // lib::pr($query);
        // lib::pr($results);
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__.":  numrows = $numrows<br>";} 
        return $results;
     }
    private function counttasks($pageid,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
       $query = <<<QUERYSTR
            SELECT id FROM task
            WHERE page_id = {$pageid}; 
            QUERYSTR;
        $success = $this->tasktable->query($query, $tasks, $taskcount,$trace);
        if ($this->trace || $trace ) { echo "LEAVE ".__METHOD__."<br>taskcount = $taskcount";} 
        return $taskcount;
     }
    private function movetostartofrecurrenceperiod ($startdaystr,$session,$trace=false) {
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $dateformat = 'Y-m-d';
        $day = new \DateTime($startdaystr);
        $recurrenceperiod = $session["recurrence"];
        switch ($recurrenceperiod) {
            case "Weekly": 
                    // we need to consider the multiple weeks as the recurring block, not a single week, so we will include 
                    // past tasks in earelier weeks than the week containing $targetdate
                    $targetdate   = new \DateTimeImmutable($startdaystr);
                    $startdate    = eem::determinefirstdateofweeklyperiod ($session["taskstartdate"],$targetdate,$session["weeklyinterval"]);
                    $startdaystr  = $startdate->format('Y-m-d');
                    break;
            case "Monthly": 
                    $startdaystr = $day->modify("first day of this month")->format($dateformat);break;
            case "Yearly": 
                    $startdaystr = $day->modify("first day of this year")->format($dateformat);break;
        }
        return $startdaystr;
     }
    private function removesessionsfromfront($sessions,$count,$trace=false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        // echo __METHOD__.count($sessions).">".$count;
       if (count($sessions) > $count) {
            // remove the current page of sessions 
            for ($i=0;$i<$count;$i++) {
                array_shift($sessions);
            }
        }
        return $sessions;
     }
    private function removefromend(&$sessions,$count,$trace=false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
       if (count($sessions) > $count) {
            // remove the current page of sessions 
            for ($i=0;$i<$count;$i++) {
                array_pop($sessions);
            }
        }
     }
    private function calculatefirstpagesize ($sessions,$pd,$trace=false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $today = date('Y-m-d');
        $firstday = $this->movetostartofrecurrenceperiod ($today,$sessions[0],$trace);
        $pastsessioncount = count(array_filter($sessions, function($row)  use ($firstday){
                                 return $row["session_start"] < $firstday;
                            }));
        return (($psc = ($pastsessioncount % $pd)) == 0) ? $pd : $psc;  
     }
    private function stripsessions($sessions,&$startdaystr,$direction,$pagedepth=0,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";}
       // we assume that all sessions supplied have the same parent task
        // lib::v($sessions);
        $dateformat = 'Y-m-d';
        $recurrenceperiod = $sessions[0]["recurrence"];
        $pd = (int)$pagedepth ?: (int)$sessions[0]["sessiondepth"];
        $firstday = $this->movetostartofrecurrenceperiod ($startdaystr,$sessions[0],$trace);
        // lib::v("A",$firstday);
        switch ($direction) {
            case "0": // goto today
                $today = new \DateTime();
                $startdaystr = $today->format($dateformat);
                $firstday = $this->movetostartofrecurrenceperiod ($startdaystr,$sessions[0],$trace);
            case "":  // don't move - just display from startdaystr
        // echo count($sessions)."-";
                while (count($sessions) &&  (reset($sessions)["session_start"] <  $firstday)) {
                    array_shift($sessions);
                }
         // echo count($sessions)."-";
                while (count($sessions) > $pd) {
                    array_pop($sessions);;
                }
         // echo count($sessions)."-";
                break;
            case "-1": // go back one page
                if (count($sessions) && reset($sessions)["session_start"] <  $firstday) { 
                    // there are earlier sessions to display so we can remove all sessions from firstday on
         // echo "!-!".count($sessions)."-";
                    while (count($sessions) && (end($sessions)["session_start"] >=  $firstday)) {
                        array_pop($sessions);
                    }
                    while (count($sessions) > $pd) {
                        array_shift($sessions);
                    }
                } else { 
         // echo count($sessions)."-";
                    // we are already at the start of the data. 
                    // Need to stay there but figure out how many sessions to display
                    // pages are blocks of $pd sessions, starting on today, 
                    // except maybe the first/last which might have fewer
                    $pd = $this->calculatefirstpagesize ($sessions,$pd,$trace);
                    while (count($sessions) > $pd) {
                        array_pop($sessions);
                    }

                }    
        // echo count($sessions)."-";
        // echo count($sessions);
                break;
            case "-2": // goto start  of data
                $ppd = $this->calculatefirstpagesize ($sessions,$pd,$trace);
                while (count($sessions) && (count($sessions) > $ppd)) {
                    array_pop($sessions);
                }
        // echo count($sessions);
                break;
            case "+1": // go forward one page
                // remove all before the first session in the current page
                // nb $startdaystr still equal incoming value
        // echo count($sessions)."-";
        // echo $firstday."|";
                if (reset($sessions)["session_start"] >=  $firstday) {
                    // we're at the start so the current page may contain < pd sessions
        // echo reset($sessions)["session_start"].">=".$firstday;
                    $ppd = $this->calculatefirstpagesize ($sessions,$pd,$trace);
                    // remove the current page of sessions 
                    $sessions = $this->removesessionsfromfront($sessions,$ppd,$trace);
                } else {
        // echo reset($sessions)["session_start"]."<=".$firstday;
                    while (count($sessions) && (reset($sessions)["session_start"] < $firstday)) {
                        array_shift($sessions);
                    }
        // echo count($sessions)."-";
                    $sessions = $this->removesessionsfromfront($sessions,$pd,$trace);
                }                                
                //  remove any beyond the required $sessioncount
         // echo count($sessions)."-";
                while (count($sessions) > $pd) {
                    array_pop($sessions);
                }
        // echo count($sessions);
                break;
            case "+2": // goto end of data
                $today = date('Y-m-d');
                $firstday = $this->movetostartofrecurrenceperiod ($today,$sessions[0],$trace);
        // echo count($sessions)."-";
                $futuresessioncount = count(array_filter($sessions, function($row) use ($firstday){
                     return $row["session_start"] >= $firstday;
                }));
        // echo $futuresessioncount."-";
                $pd = ($psc = ($futuresessioncount % $pd)) == 0 ? $pd : $psc;
                while (count($sessions) > $pd) {
                    array_shift($sessions);
                }
            // echo count($sessions);
                break;
            default:   
                break;
        } 
        // lib::v(count($testresults),$startdaystr,$direction,$sessioncount);
        $startday = new \DateTime(count($sessions)?reset($sessions)["session_start"]:"");
        $startdaystr = $startday->format($dateformat);
        return $sessions;
     }    
    public function loadroster($pagenum,&$pagedepth,&$startdatestr,$include_unpublished,&$page_id,&$rostersessions,$trace=false){
        // currently $generatesessions is always passed in as true.
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>pagenum = $pagenum<br>";} 
        $requestdata = $this->session->getrequestdata();
       if ($this->trace || $trace ) { lib::v("loadroster",$requestdata);} 
        $pagedepth = trim($requestdata["pagedepth"] ?? "") ?: $this->rosterpagerows;
        $startdatestr =  $requestdata["firstdate"] ?? date('Y-m-d');
        $direction = trim($requestdata["direction"] ?? "");
        $success = $this->pagetable->selectononefield("pagenumber",$pagenum,$page,$numrows,false,false);
        if ($success && $numrows==1) {
            $page_id = $page[0]["id"];
            $taskcount = $this->counttasks($page[0]["id"],$trace);
                                   if ($this->trace || $trace ) {  lib::v("f",$page,$pagedepth,$taskcount,"====");}
            $roster = [];
            $this->rostertable->selectonID($page_id, $roster, $rnumrows, false, false);
            if ($rnumrows && ($roster[0]["autoextendtasks"] ?? false)) {
                $query = "SELECT * FROM task WHERE page_id = {$page_id};";
                if ($this->tasktable->query($query,$tasks,$numrows,$trace)) {
                    foreach ($tasks as $task) {
                        $this->taskextendermanager->extendsessions($task["id"],$this->errorhandler,"Page {$pagenum} launch.",$trace);
                    }
                }
            }
            // first LOAD all sessions for tasks on this page, respecting the '$include_unpublished' parameter  
            $success =  $this->loadallsessions($page_id,true,$allsessions,$numrows);
            $allsessions = $allsessions ?? [];
                               if ($this->trace || $trace ) { lib::v("g",$success, $allsessions);}
            // if (count($allsessions)) {
            $results = [];
            $taskids = array_unique(array_column($allsessions, "task_id"));
                               if ($this->trace || $trace ) {lib::v("A",$taskids);} 
            $holddate = $startdatestr;
            foreach ($taskids as $taskid) {
                               if ($this->trace || $trace ) { lib::v("B",$taskid);} 
                $tasksessions = array_filter($allsessions, function($row) use ($taskid,$include_unpublished)  {
                                        if (!$include_unpublished) {
                                            return $row['task_id'] == $taskid && $row['published'] == 1;
                                        } else {
                                            return $row['task_id'] == $taskid;
                                        }
                                    });
                               if ($this->trace || $trace)  { lib::v("C",count($tasksessions));} 
                if (count($tasksessions)) {
                    // code...
                    $datesortedresults = lib::array_orderby($tasksessions,"session_start", SORT_ASC);
                    $startdatestr = $holddate;
                    // lib::v(count($tasksessions),$holddate,$startdatestr,$periodstartday->format('y-m-d')); 
                    // lib::pr(count($tasksessions),$tasksessions); 
                    $datesortedresults = $this->stripsessions($datesortedresults,$startdatestr,$direction,$pagedepth);
                    // lib::pr(count($tasksessions),$tasksessions); 
                    // $datesortedresults = $this->stripsessions($datesortedresults,$startdatestr,$direction);
                                   if ($this->trace || $trace ) { lib::v("C",count($datesortedresults));} 
                    // lib::v($datesortedresults); 
                    $results = array_merge($results,$datesortedresults);
                }
            }
               if ($this->trace || $trace ) { lib::v("D",count($results));} 
            $rostersessions = lib::array_orderby($results,"taskgroup",SORT_ASC,"groupindex",SORT_ASC,"session_start",SORT_ASC);
            // }
            // lib::v($rostersessions);
        }
        if ($this->trace || $trace  ) {echo "Leave ".__METHOD__."  ({$numrows} rows)<br>";}
        return $success;
     }
    public function loadboookings(&$results,$trace= false){
       if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $query = <<<QUERYSTR
        SELECT   b.id AS booking_id
                 ,sr.id AS session_role_id
                 ,sr.role_id AS role_id
                 ,u.id as user_id  
                 ,u.display_name AS display_name
                 ,u.given_name AS given_name
                 ,u.family_name AS family_name
        FROM booking b 
        JOIN session_role sr ON sr.id = b.session_role_id
        JOIN user u ON u.id = b.user_id
        WHERE b.status = 'booked'
        ORDER BY b.id;
        QUERYSTR;
        $success = $this->bookingtable->query($query,$results, $numrows,$trace);
       if ($this->trace || $trace ) {echo "Leave ".__METHOD__."  ({$numrows} rows)<br>";}
        return $success;
     }
}
 