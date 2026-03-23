<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class TaskManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Task";
    protected $linkedobject = "role";
    protected $db;
    public function __construct(protected \apptable\TaskTable $table,
                                protected \apptable\PageTable $pagetable,
                                protected \apptable\TaskRoleTable $taskroletable,
                                protected \apptable\RoleTable $roletable,
                                protected \app\controller\manager\SessionManager $sessionmanager,
                                protected \app\controller\manager\TaskExtenderManager $taskextendermanager
                            ) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo gtab(-1)."Leave ".__METHOD__."<br>";}
     }
    public function init($session){
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->sessionmanager->init($this->session);
        $this->taskextendermanager->init($this->session);
        $this->pagetable->init($this->db); 
        $this->taskroletable->init($this->db); 
        $this->roletable->init($this->db); 
     }
    protected function getparents(&$parents,$trace) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $success = $this->pagetable->select("id,menutext","pagetype = '2'", "","","menutext",0,$parents,$numrows,false);
        if ($success && count($parents) == 0) {
            $success = $this->pagetable->select("id,menutext","","","","",0,$parents,$numrows,false);
        }
        return true;
     }
    protected function setdefaults(&$fields,$trace=false){
        $fields['dailyoption'] = "1";
        $fields['dailyinterval'] = "1";
        $fields['weeklyinterval'] = "1";
        $fields['weeklydow'] = "126";
        $fields['monthlyoption'] = "1";
        $fields['monthlydayofmonth'] = "1";
        $fields['monthlyinterval0'] = "1";
        $fields['monthlywhichdow'] = "3";
        $fields['monthlydow'] = "5";
        $fields['monthlyinterval1'] = "1";
        $fields['yearlydom'] = "1";
     }    
    public function getallroles(&$roles,&$numrows=0,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $roles = array();
        $success = $this->roletable->selectall($roles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo gtab(-1)."Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getalltaskroles(&$taskroles,&$numrows,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $taskroles = array();
        $success = $this->taskroletable->selectall($taskroles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo gtab(-1)."Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function loadlinkedobjects($task_id,&$taskroles,$numrows,$trace=false){
        // load all the taskrole records for this task into the taskrole table obj then load the roles
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."(task = ".$task_id.")<br>\n"; }
        $taskroles = array();
        $success = $this->roletable->getrolesfortasks($task_id,$taskroles,$numrows);
        $r = count($taskroles);
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__." success = {$success} id = {$task_id},  {$r} roles<br>\n"; }
        return $success;
     }
    protected function deletelink($task_id,$role_id,$trace=false) { 
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__." task_id=".$task_id." <br>"; }
         $whereclause =  "`task_id` = '{$task_id}' AND `role_id` = '{$role_id}'"; 
         $success =   $this->taskroletable->delete($whereclause,$numrows,false);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." success=$success<br>"; }
        return $success ;
     }
    protected function insertlink($task_id,$role_id,$trace=false) { 
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." task_id=".$task_id." <br>"; }
        $this->taskroletable->clear();
        $this->taskroletable->setfield("task_id",$task_id);
        $this->taskroletable->setfield("role_id",$role_id);
        $success = $this->taskroletable->insert(false);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    protected function updatelinkfields($weblinkedfields,$main_id,$trace,&$errormessage="") { 
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." task_id=".$main_id." <br>"; }
        $updatecount = 0;
        foreach ($weblinkedfields as $key => $fielddata) {
            $roleid =  substr($key,0,strpos($key,"_"));
            $fieldname = substr($key,strpos($key,"_")+1);
            $whereclause =  "`task_id` = '{$main_id}' AND `role_id` = '{$roleid}'";
            $success = $this->taskroletable->select("id",$whereclause,'','','',0,$result,$numrows,$trace);
            if ($numrows == 1) { // if not ==1, we can assume the link has been deleted
                $setclause = "`".$fieldname."`='".trim($fielddata)."'";
                $whereclause = " id=".$result[0]["id"];
                $success = $this->taskroletable->update($setclause, $whereclause,$numrows,$errormessage,$trace,$matchedrows,false);
                $updatecount++;
            }
        }
        if ($updatecount) {
            $success = $this->sessionmanager->checkSessionrolesAgainstTaskRoles($main_id,$errormessage,$trace);
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__." success={$success}<br>{$errormessage}"; }
        return $success;
     }
    public function performaction($action,&$outcomemessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        switch ($data["action"]) {
            case "buildsessions" : 
                $outcomemessage = $this->taskextendermanager->extendsessions($this->requestdata['id'],$trace);
                $success = true;
                break;
            default:
                $outcomemessage="Invalid Action in request: $this->requestdata['action']";
                $success = false;
        } 
        if ($this->trace || $trace ) {echo gtab(-1)."Leave ".__METHOD__." ".$errormessage." <br>";}
        return $success;
     }
    public function getattendancetasks(&$taskdata,&$numrows,$trace=false) {
       if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $taskdata = array();
        $query = <<<SQL
            SELECT * 
            FROM task  
            WHERE logattendance = 1
            ORDER BY id
        SQL;
        $success = $this->table->query($query,$taskdata,$numrows,$trace);
        if ($this->trace  || $trace ) {echo gtab(-1)."Leave ".__METHOD__."  success =  {$success}<br>";}
        if ($this->trace ) {echo gtab(-1)."Leave ".__METHOD__."<br>";}
        return $success;
     } 
}
