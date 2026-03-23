<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class RoleManager extends \fw\controller\manager\StdManager {   
    private $trace=false;
    protected $db;
    protected $name = "Role";
    protected $linkedobject = "pageaction";
    public function __construct(protected \apptable\RoleTable $table,
                                protected \apptable\PageActionTable $pageactiontable,
                                protected \apptable\RolePageactionTable $pageactionroletable,
                                protected \apptable\PageTable $pagetable
                                ){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function init($session){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->pagetable->init($this->db);
        $this->pageactiontable->init($this->db);
        $this->pageactionroletable->init($this->db);
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
     }
// ======================================= functions related to managing linked objects (Action)
    public function loadlinkedobjects($role_id,&$pageactions,$numrows,$trace=false){
        if ($this->trace) { echo "Enter ".__METHOD__." role id=".$role_id." <br>"; }
        // load all the pageaction records for this role into the pageactiontable obj then load the pages
        $pageactions = array();
        $success = $this->pageactionroletable->getrolepageactionsforrole($role_id,$rolepageactions,$numrows);
        // lib::v($rolepageactions);
        if ($success && $numrows) {
            foreach ($rolepageactions as $rpa) {
                $success = $this->pageactiontable->getpageaction($rpa["pageaction_id"],$pageaction,$numrows,false,false);
        // lib::v($rpa,$rolepageactions);
                if ($success  && $numrows) {
                    $pageactions[] = $pageaction;
                }
            }
        }
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    public function deletelink($role_id,$pageaction_id) { 
        if ($this->trace) { echo "Enter ".__METHOD__." role id=".$role_id." <br>"; }
         $whereclause =  "`role_id` = '{$role_id}' AND `pageaction_id` = '{$pageaction_id}'"; 
         $success =   $this->pageactionroletable->delete($whereclause,$numrows,false);
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    public function insertlink($role_id,$pageaction_id) { 
        if ($this->trace) { echo "Enter ".__METHOD__." role id=".$role_id." <br>"; }
        $this->pageactionroletable->clear();
        $this->pageactionroletable->setfield("role_id",$role_id);
        $this->pageactionroletable->setfield("pageaction_id",$pageaction_id);
        $success = $this->pageactionroletable->insert(false);
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
//============================================================================================
    public function getallpageactionroles(&$pageactionroles,&$numrows,$orderby="",$trace=false){
        // called from fiewcontroller
        $pageactionroles = array();
        $success = $this->pageactionroletable->selectall($pageactionroles,$numrows,$orderby,$trace);
        return $success;
     }
    public function getallpageactions(&$pageactions,&$numrows=0,$orderby="",$trace=false){
        // called from fiewcontroller
        $pageactions = array();
        $success = $this->pageactiontable->getallpageactions($pageactions,$numrows,$orderby,$trace);
        return $success;
     }
    public function getpageswithactions(&$pages,&$numrows=0,$orderby="",$trace=false){
        // called from viewcontroller
        $pages = array();
        $success = $this->pagetable->select("id,menutext","(pagetype = 2 OR pagetype = 3)",'','',"pagetype,menutext",0,$pages,$numrows,false,false);
        return $success;
     }
 }
