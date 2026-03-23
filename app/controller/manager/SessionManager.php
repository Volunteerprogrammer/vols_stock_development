<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class SessionManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Session";
    protected $linkedobject = "";
    protected $db;
    public function __construct(protected \apptable\SessionTable $table,
                                protected \apptable\TaskTable $tasktable,
                                protected \apptable\TaskRoleTable $taskroletable,
                                protected \apptable\SessionRoleTable $sessionroletable,
                                protected \apptable\ClientSessionTable $clientsessiontable,
                                protected \apptable\RoleTable $roletable,
                                protected \apptable\PageTable $pagetable,
                                protected \apptable\BookingTable $bookingtable
                            ){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function init($session){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->tasktable->init($this->db); //
        $this->taskroletable->init($this->db); // 
        $this->sessionroletable->init($this->db); // 
        $this->clientsessiontable->init($this->db);
        $this->roletable->init($this->db);
        $this->pagetable->init($this->db);
        $this->bookingtable->init($this->db);
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function select($fieldselection, $whereclause, $groupby,$having,$orderby,$locktype, &$results, &$numrows=0, $trace=false, $noerrorhandler=false) {
        return $this->table->select($fieldselection,$whereclause,$groupby,$having,$orderby,$locktype,$results,$numrows, $trace,$noerrorhandler) ;
     }
    public function putfields(&$id,$fieldnames,$data,$save,&$numrows,&$errormessage,$trace=false) {
        return $this->table->putfields($id,$fieldnames,$data,$save,$numrows,$errormessage,$trace);
     }
    protected function getparents(&$parents,$trace) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->tasktable->selectall($tasks,$numrows,"",$trace);
        $parents["task"] = $tasks;
        $success = $this->pagetable->selectall($pages,$numrows,"",$trace);
        $parents["page"] = $pages;
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    protected function setdefaults(&$fields,$trace=false){
        $fields['is_holiday'] = "0";
        $fields['published'] = "0";
     }    
    public function insertnewsession(&$id,$fieldnames,$data,&$numrows,&$errormessage,$trace=false){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->putfields($id,$fieldnames,$data,true,$numrows,$errormessage,$trace=false);
        if ($success && $errormessage == "") {
            $task_id = $data[array_search("task_id",$fieldnames)];
            $success = $this->taskroletable->select("*", "task_id = {$task_id}","","","",0,$taskroles,$nrows,false);
            if ($success) {
                foreach ($taskroles as $key => $taskrole) {
                    $fieldnames = ["session_id","role_id","min_quantity","max_quantity","waitlist"];
                    $data       = [$id,$taskrole["role_id"],$taskrole["min_quantity"],$taskrole["max_quantity"],0];
                    $sr_id = 0;
                    $success = $this->sessionroletable->putfields($sr_id,$fieldnames,$data,true,$srrows,$srerrormessage,$trace=false);
                }
            }
        }
        return $success;
     }
    public function getvolunteers( &$volunteers, &$numrows, $trace=false) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $query = <<<QUERY
        SELECT GROUP_CONCAT(CONCAT_WS(" ",u.given_name,u.family_name) SEPARATOR ", ")  as volunteername,
               sr.session_id as session_id
        FROM booking b
        JOIN user u ON u.id = b.user_id
        JOIN session_role sr ON sr.id = b.session_role_id
        WHERE b.status = 'booked' 
        GROUP BY session_id
        ORDER BY session_id
        QUERY;
        $success = $this->table->query($query,$volunteers,$numrows,$trace);
        // lib::v($query,$success,$volunteers);
        return $success;
     }
// ========================================================================= METHODS CONCERNED WITH EXTENDING A ROSTER'S SESSIONS
    public function addsessions($pageid,$from,$to,$trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $this->today = new \DateTimeImmutable();
        $this->todaydow = $this->today->format("w");
        $query = <<<  SQL
                SELECT  * 
                FROM task
                WHERE page_id = {$pageid}
                SQL;
        $success = $this->sessiontable->query($query,$tasks,$numrows,$trace);
        if ($success) {
            foreach ($tasks as $task) {
                switch ($task["recurrence"]) {
                    case "Once-only": $this->onceonly($task);break;
                    case "Daily"    : $this->daily($task);break;
                    case "Weekly"   : $this->weekly($task);break;
                    case "Monthly"  : $this->monthly($task);break;
                    // case "Yearly"   : $this->yearly($task);break;
                    default:
                } 
            }
        }
        return $success;
     }
    public function addmonths($thisdate,$ordidx,$dow,$months,$trace =false) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $ordinal = ["first","second","third","fourth","last"][$ordidx];
        $dayname = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"][$dow];
        $monthnames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        $thisyear = (int) $thisdate->format("Y");
        $thismonth = (int) $thisdate->format("m");
        $nextmonth = (($thismonth+$months - 1) % 12) + 1;
        if ($nextmonth < $thismonth) { // we have gone to a new year
            $thisyear++;
        }
        $datestring = "{$ordinal} {$dayname} of {$monthnames[$nextmonth-1]} {$thisyear}";
        $newdate = new \DateTimeImmutable($datestring);
        // echo $thisdate->format("d/m/Y")." // ".$datestring." // ".$newdate->format("d/m/Y")."<br>";
        return $newdate;
     }    
    public function generatepublish($task,$taskday,$lastpublishedday=null,&$report=null,$trace =false) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $fieldnames = [];
        $whereclause = "`task_id` = '{$task["id"]}' AND `start` LIKE '{$taskday->format("Y-m-d")}%'";
        $success = $this->table->select("id,published",$whereclause,"","","",0,$session,$umrows,false) ;
        $id = $session[0]["id"]??0;
        $published = $session[0]["published"]??0;
        $data = [];
        if ($id == "0") { // there is no session for this date yet - create and publish if required
            $fieldnames=["task_id","start","finish","is_holiday","holiday_name","published"];
            $dopublish = isset($lastpublishedday) && ($taskday <= $lastpublishedday)? "1":"0";
            $data=[$task["id"],$taskday->format("Y-m-d")." ".$task["starttime"],$taskday->format("Y-m-d")." ".$task["endtime"],0,"", $dopublish];
            $success = $this->insertnewsession($id,$fieldnames,$data,$numrows,$errormessage,$trace=false);
            if (isset($report)) {
                $report .= date('Y-m-d H:i:s').": adding ".($dopublish?"and publishing ":"")." a session for {$task["name"]} at {$taskday->format("Y-m-d")} {$task["starttime"]}<br>\n";
            }
        } elseif (isset($lastpublishedday) && !$published && ($taskday <= $lastpublishedday)) { // publish it
            $fieldnames=["published"];
            $data = ["1"];
            $success = $this->table->putfields($id,$fieldnames,$data,true,$numrows,$errormessage,$trace=false);
            if (isset($report)) {
                $report .= date('Y-m-d H:i:s').": publishing existing session for {$task["name"]} at {$taskday->format("Y-m-d")} {$task["starttime"]}<br>\n";
            }
        } else {
        // echo $task["id"].": ".$taskday->format("Y-m-d")." NO ACTION   {$task["name"]}<BR>";
        }   
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." success=$success<br>"; }
        return $success;     
     }      
    public function sessiontablequery ($query,&$results,&$numrows,$trace=false ) {
        return $this->table->query($query,$results,$numrows,$trace);
     }
    public function checkSessionrolesAgainstTaskRoles($task_id,&$errormessage="",$trace=false){
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." task_id=".$task_id." <br>"; }
        // for all sessions for a given task, check that the session_role values (max_quantity, min_quantity, waitlist) are equal to 
        // those in the corresponding task_role templates. If not, update. We need to check whether the number of current bookings now
        // exceeds a new max_quantity, in which case leave the bookings but report it to the user.
        $warnings = [];
        try {
            $success = $this->tasktable->selectonID($task_id,$task,$numrows,0,false);
            $success = $this->taskroletable->selectononefield("task_id",$task_id,$taskroles,$numrows,false,false);
            if ($success && is_array($taskroles)) {
                foreach ($taskroles as $taskrole) {
                    $query = <<<SQL

                                SELECT sr.* 
                                FROM session_role sr 
                                JOIN session s ON sr.session_id = s.id
                                WHERE s.task_id = {$task_id} AND sr.role_id = {$taskrole["role_id"]} AND s.start > CURDATE()

                    SQL;
                    $success = $this->sessionroletable->query($query,$sessionroles,$numrows,$trace);
                    // now test each session_role agaist its task_role template
                    foreach ($sessionroles??[] as $sessionrole) {
                        if ($sessionrole['min_quantity'] <> $taskrole['min_quantity'] or $sessionrole['max_quantity'] <> $taskrole['max_quantity'] or $sessionrole['waitlist'] <> $taskrole['waitlist']) {
                            // if ($taskrole['max_quantity'] < $sessionrole['max_quantity']) { // max is reducing - there may be too many existing bookings 
                                // count the current bookings
                                $success = $this->bookingtable->countrecords("(session_role_id = {$sessionrole['id']}) AND status = 'booked'",$numrows,$trace);

                                if ($success && ($numrows > $taskrole['max_quantity'])) {
                                    // accumulate a report 
                                    $success = $this->roletable->selectonID("{$sessionrole['role_id']}",$role,$numrows,0,$trace=false);
                                    $success = $this->table->selectonID("{$sessionrole['session_id']}",$session,$numrows,0,$trace=false);
                                    $warnings[] = [ $task['name'], $role['name'], $session['start'] ];
                                }
                            // }
                            $set_clause = "min_quantity =  {$taskrole['min_quantity']},max_quantity =  {$taskrole['max_quantity']},waitlist =  {$taskrole['waitlist']}";
                            $where_clause = "id = {$sessionrole['id']}";
                            $success = $this->sessionroletable->update($set_clause, $where_clause, $numrows,$errormessage,$trace);
                        }
                    }
                }
            }
            if ($count = count($warnings)) {
                $errormessage .= "<strong>Following these changes, the following {$count} sessions are now overbooked</strong>:<BR>\n<BR>\n";
                foreach ($warnings as $warning) {
                    $errormessage .= $warning[0].": Role ".$warning[1]." on ".$warning[2]."<BR>\n";
                }
            }
            if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." success=$success<br>{$errormessage}";lib::pr($warnings);}
        } catch (\Exception $e) {
            $errormessage .= $e->getMessage(); 
            if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." $errormessage<br>"; }
        }
     }
// ============================  ======================================= METHODS CONCERNED WITH VIEWING ATTENDANCE IN THE SESSION EDITOR
    public function getattendances(&$clientsessions,$numrows,$trace) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = " ";
        $query .= <<<SQL
                    SELECT session_id,group_concat(CONCAT(given_name," ",family_name,",",household) separator "|") as clients
                    FROM (SELECT session_id,c.id,given_name,family_name,count(cm.id) as household
                            FROM client_session cs
                            JOIN client c ON c.id = cs.client_id
                            LEFT OUTER JOIN client_member cm ON c.id = cm.client_id
                            GROUP BY session_id,c.id) As sql1
                    GROUP BY session_id
                    ORDER BY session_id;

                SQL; 
        $success = $this->clientsessiontable->query($query,$clientsessions,$numrows,$trace);
        // lib::v($results);
        if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows OK? = {$success})<br>";}
        return $success;
     }
// ============================  ======================================= METHODS CONCERNED WITH SESSION ATTENDANCE TRACKING
    public function getattendancesessionlist(&$sessions,&$numrows=0,$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $sessions = array();
        $query = <<<SQL
            SELECT s.id as session_id, SUBSTRING(t.name,11) as name,s.start as start
            FROM session s
            JOIN task t ON s.task_id = t.id 
            WHERE t.logattendance = 1 AND is_holiday <> 1
            ORDER BY s.start
        SQL; 
        $success = $this->table->query($query,$sessions,$numrows,$this->trace||$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getclientsessions(&$clientsessions,&$numrows,$trace =true) {
        // return true;
        // echo "here<br>";
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $sessiondata = array();
        $query = <<<SQL
            SELECT id,session_id, client_id
            FROM client_session 
            ORDER BY session_id, client_id
        SQL;
        $success = $this->clientsessiontable->query($query,$clientsessions,$numrows,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getfbsessionvolunteers($todaydate,&$volunteers,&$numrows,$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $volunteers = array();
        $query = <<<SQL
                    SELECT  u.id as id
                            ,CONCAT(u.given_name," ",family_name) as name
                    FROM user u
                    JOIN booking b ON b.user_id = u.id 
                    JOIN session_role sr ON b.session_role_id = sr.id 
                    JOIN session s ON  sr.session_id = s.id  
                    JOIN task t ON  s.task_id = t.id  
                    WHERE s.start LIKE "{$todaydate}%" AND t.logattendance = 1 AND b.status = "booked";
        SQL;
        $success = $this->table->query($query,$volunteers,$numrows,$trace);
// lib::pr($volunteers);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
//================================================================= the following are AJAX calls from the RequestController
    // these are for the Attendance page
    public function addclientsession($sesion_id,$client_id) {
        $id = 0;
        $success = $this->clientsessiontable->putfields($id,["session_id","client_id"],[$sesion_id,$client_id],true,$numrows,$errormessage,false);
        if ($success) {
            return "OK:".$id;
        } else {
            return "!!".$errormessage;
        }
     }    
    public function deleteclientsession($clientsession_id) {
       $success = $this->clientsessiontable->delete("`id`={$clientsession_id}",$numrows,false,$errormessage);
        if ($success) {
            return "OK";
        } else {
            return "!!"+$errormessage;
        }
     }    
    public function getbookinghistory( &$history,$sessionid, &$numrows, $trace=false) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $whereclause = ($sessionid !== 0)? "WHERE sr.session_id = $sessionid" : "";
        $query = <<<SQL
                SELECT session_id,group_concat(bookingaction ORDER BY sorttime SEPARATOR "!!") AS actions    
                    FROM
                        (SELECT session_id,sessiontime,sorttime,concat_ws("|", booking_id,vol,action,byperson,actiontime) AS bookingaction
                         FROM 
                            (SELECT sr.session_id as session_id,
                                    s.start as sessiontime, 
                                    b.id as booking_id,
                                    concat_ws(' ',concat("<strong>",u.given_name,"</strong>"),u.family_name) as vol,
                                    "Booked" as action,
                                    concat_ws(' ',ubook.given_name,ubook.family_name) as byperson,
                                    DATE_FORMAT(b.booked_time,'%Y%m%d%H%i%s') as sorttime,
                                    DATE_FORMAT(b.booked_time,'%a, %e %b %Y  at  %h:%i.%s %p') as actiontime
                                FROM booking b
                                JOIN user u ON u.id = b.user_id
                                LEFT JOIN user ubook ON ubook.id = b.booked_by
                                JOIN session_role sr ON sr.id = b.session_role_id
                                JOIN session s ON s.id = sr.session_id
                                $whereclause 
                            UNION
                            SELECT  sr.session_id as session_id,
                                    s.start as sessiontime, 
                                    b.id as booking_id,
                                    concat_ws(' ',concat("<strong>",u.given_name,"</strong>"),u.family_name),
                                    "Deleted" as action,
                                    concat_ws(' ',udel.given_name,udel.family_name) as byperson,
                                    DATE_FORMAT(b.deleted_time,'%Y%m%d%H%i%s') as sorttime,
                                    DATE_FORMAT(b.deleted_time,'%a, %e %b %Y  at  %h:%i.%s %p') as actiontime
                                FROM booking b
                                JOIN user u ON u.id = b.user_id
                                JOIN user udel ON udel.id = b.deleted_by
                                JOIN session_role sr ON sr.id = b.session_role_id
                                JOIN session s ON s.id = sr.session_id
                                $whereclause
                            ) AS SQL1
                        ORDER BY sorttime
                        ) AS SQL2
                   GROUP BY session_id
                   ORDER BY sessiontime;
                SQL;
        $success = $this->table->query($query,$history,$numrows,$trace);
        // lib::v($query);
        return $success;
     }
}