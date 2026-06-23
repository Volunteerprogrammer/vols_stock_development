<?php
namespace app\controller\manager;

class EventManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Event";
    protected $db;
    protected $config;
    protected $linkedobject = "role";
    public function __construct(protected \database\table\EventTable $table,
                                protected \database\table\PageTable $pagetable,
                                 protected \database\table\EventRoleTable $eventroletable,
                                protected \database\table\RoleTable $roletable) {
       if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function init($session){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->config = $this->session->getconfig();
        $this->pagetable->init($this->db); 
        $this->eventroletable->init($this->db); 
        $this->roletable->init($this->db); 
     }
    protected function getparents(&$parents,$trace) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->pagetable->select("id,name","pagetype = '2'", "","","name",0,$parents,$numrows,false);
        if ($success && count($parents) == 0) {
            $success = $this->pagetable->select("id,name","",0,$parents,$numrows,false);
        }
        return true;
     }
    protected function updatesetclause(){
        $set = " `name` = '{$this->requestdata['name']}'";
        $set .= ", `page_id` = '{$this->requestdata['page_id']}'";
        $set .= ", `starttime` = '{$this->requestdata['starttime']}'";
        $set .= ", `endtime` = '{$this->requestdata['endtime']}'";
        $set .= ", `leadtime` = '{$this->requestdata['leadtime']}'";
        $set .= ", `publishedleadtime` = '{$this->requestdata['publishedleadtime']}'";
        $set .= ", `recurrence` = '{$this->requestdata['recurrence']}'";

        $set .= ", `dailyoption` = '".($this->requestdata['dailyoption']??1)."'";
        $set .= ", `dailyinterval` = '".($this->requestdata['dailyinterval']??1)."'";

        $set .= ", `weeklyinterval` = '".($this->requestdata['weeklyinterval']??1)."'";
        $set .= ", `weeklydow` = '".($this->requestdata['weeklydow']??126)."'";
        
        $set .= ", `monthlyoption` = '".($this->requestdata['monthlyoption']??1)."'";
        $set .= ", `monthlydayofmonth` = '".($this->requestdata['monthlydayofmonth']??1)."'";
        $set .= ", `monthlyinterval0` = '".($this->requestdata['monthlyinterval0']??1)."'";
        $set .= ", `monthlywhichdow` = '".($this->requestdata['monthlywhichdow']??3)."'";
        $set .= ", `monthlydow` = '".($this->requestdata['monthlydow']??5)."'";
        $set .= ", `monthlyinterval1` = '".($this->requestdata['monthlyinterval1']??1)."'";
        
        $set .= ", `yearlyoption` = '".($this->requestdata['yearlyoption']??0)."'";
        $set .= ", `yearlydom` = '".($this->requestdata['yearlydom']??1)."'";
        $set .= ", `yearlymonth0` = '".($this->requestdata['yearlymonth0']??0)."'";
        $set .= ", `yearlywhichdom` = '".($this->requestdata['yearlywhichdom']??0)."'";
        $set .= ", `yearlywhichday` = '".($this->requestdata['yearlywhichday']??0)."'";
        $set .= ", `yearlymonth1` = '".($this->requestdata['yearlymonth1']??0)."'";
        $set .= ", `pageindex` = '".($this->requestdata['pageindex']??0)."'";
        $set .= ", `pagedepth` = '".($this->requestdata['pagedepth']??0)."'";
        $set .= ", `bookingalertlevels` = '".($this->requestdata['bookingalertlevels']??"")."'";
        $set .= ", `bookingalertperiods` = '".($this->requestdata['bookingalertperiods']??"")."'";
        $set .= ", `startdate` = '{$this->requestdata['startdate']}'";
        return $set;
     }
    protected function insertsetfields(){
        $this->table->setfield("name",$this->requestdata['name']);
        $this->table->setfield("page_id",$this->requestdata['page_id']);
        $this->table->setfield("starttime",$this->requestdata['starttime']);
        $this->table->setfield("endtime",$this->requestdata['endtime']);
        $this->table->setfield("leadtime",$this->requestdata['leadtime']);
        $this->table->setfield("publishedleadtime",$this->requestdata['publishedleadtime']);

        $this->table->setfield("bookingalertlevels",($this->requestdata['bookingalertlevels']??0));
        $this->table->setfield("bookingalertperiods",($this->requestdata['bookingalertperiods']??0));

        $this->table->setfield("recurrence",$this->requestdata['recurrence']);
 
        $this->table->setfield("dailyoption",($this->requestdata['dailyoption']??1));
        $this->table->setfield("dailyinterval",($this->requestdata['dailyinterval']??1));

        $this->table->setfield("weeklyinterval",($this->requestdata['weeklyinterval']??1));
        $this->table->setfield("weeklydow",($this->requestdata['weeklydow']??126));
        
        $this->table->setfield("monthlyoption",($this->requestdata['monthlyoption']??1));
        $this->table->setfield("monthlydayofmonth",($this->requestdata['monthlydayofmonth']??1));
        $this->table->setfield("monthlyinterval0",($this->requestdata['monthlyinterval0']??1));
        $this->table->setfield("monthlywhichdow",($this->requestdata['monthlywhichdow']??3));
        $this->table->setfield("monthlydow",($this->requestdata['monthlydow']??5));
        $this->table->setfield("monthlyinterval1",($this->requestdata['monthlyinterval1']??1));
        
        $this->table->setfield("yearlyoption",($this->requestdata['yearlyoption']??0));
        $this->table->setfield("yearlydom",($this->requestdata['yearlydom']??1));
        $this->table->setfield("yearlymonth0",($this->requestdata['yearlymonth0']??0));
        $this->table->setfield("yearlywhichdom",($this->requestdata['yearlywhichdom']??0));
        $this->table->setfield("yearlywhichday",($this->requestdata['yearlywhichday']??0));
        $this->table->setfield("yearlymonth1",($this->requestdata['yearlymonth1']??0));
        $this->table->setfield("pageindex",($this->requestdata['pageindex']??0));
        $this->table->setfield("pagedepth",($this->requestdata['pagedepth']??0));

        $this->table->setfield("startdate",$this->requestdata['startdate']);
     }
    public function getallroles(&$roles,&$numrows=0,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $roles = array();
        $success = $this->roletable->selectall($roles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getalleventroles(&$eventroles,&$numrows,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $eventroles = array();
        $success = $this->eventroletable->selectall($eventroles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function loadlinkedobjects($event_id,&$eventroles,$numrows,$trace=false){
        // load all the eventrole records for this event into the eventrole table obj then load the roles
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__."(event = ".$event_id.")<br>\n"; }
        $eventroles = array();
        $success = $this->roletable->getrolesforevent($event_id,$eventroles,$numrows);
        $r = count($eventroles);
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__." success = {$success} id = {$event_id},  {$r} roles<br>\n"; }
        return $success;
     }
    protected function deletelink($event_id,$role_id,$trace=false) { 
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__." event_id=".$event_id." <br>"; }
         $whereclause =  "`event_id` = '{$event_id}' AND `role_id` = '{$role_id}'"; 
         $success =   $this->eventroletable->delete($whereclause,$numrows,false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success ;
     }
    protected function insertlink($event_id,$role_id,$trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." event_id=".$event_id." <br>"; }
        $this->eventroletable->clear();
        $this->eventroletable->setfield("event_id",$event_id);
        $this->eventroletable->setfield("role_id",$role_id);
        $success = $this->eventroletable->insert(false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    protected function updatelinkfields($weblinkedfields,$event_id,$trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." event_id=".$event_id." <br>"; }
        foreach ($weblinkedfields as $key => $fielddata) {
            $roleid =  substr($key,0,strpos($key,"_"));
            $fieldname = substr($key,strpos($key,"_")+1);
            $whereclause =  "`event_id` = '{$event_id}' AND `role_id` = '{$roleid}'";
            $success = $this->eventroletable->select("id",$whereclause,'','','',0,$result,$numrows,false);
            // \lib\StdLib::pr($key,$fielddata,$roleid,$fieldname,$whereclause,$numrows,$result);
            if ($numrows == 1) { // if not, we can assume the link hs been deleted
                $setclause = "`".$fieldname."`='".trim($fielddata)."'";
                $whereclause = " id=".$result[0]["id"];
                $success = $this->eventroletable->update($setclause, $whereclause,$numrows,$errormessage,false,$matchedrows,false);
            }
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
}
