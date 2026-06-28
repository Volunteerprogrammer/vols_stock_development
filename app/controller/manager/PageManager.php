<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class PageManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Page";
    protected $db;
    protected $pages = [];
    protected $linkedobject = "action";
    public function __construct(protected \apptable\PageTable $table,
                                protected \apptable\ActionTable $actiontable,
                                protected \apptable\PageActionTable $pageactiontable){
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        if ($this->trace ) {echo gtab(-1)."Leave ".__METHOD__."<br>\n";}
     }
    public function init($session,$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        parent::init($session);
        $this->pages = [];
        $this->pageactiontable->init($this->db);
        $this->actiontable->init($this->db);
        $success = $this->table->selectall($records,$numrows,"pagenumber",$trace,true);
        foreach ($records as  $record) {
            $this->pages[$record["pagenumber"]] = $record;
        }
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__." page count = ".count($this->pages)."<br>\n"; }
     } 
    protected function setdefaults(&$fields,$trace=false){
        $fields["unrestricted"] = 0;
     }
// ======================================= functions related to managing linked objects (Action)
    public function loadlinkedobjects($page_id,&$actions,$numrows,$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        // load all the pageaction records for this page into the pageactiontable obj then load the pages
        $pageactions = array();
        $success = $this->actiontable->getactionsforpage($page_id,$pageactions,$numrows);
        if ($success && $numrows) {
            foreach ($pageactions as $row) {
                $success = $this->actiontable->selectonID($row["action_id"],$action,$numrows,false,false);
                if ($success  && $numrows) {
                    $actions[] = $action;
                }
            }
        }
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
    public function deletelink($page_id,$action_id,$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
         $whereclause =  "`page_id` = '{$page_id}' AND `action_id` = '{$action_id}'"; 
         $success =   $this->pageactiontable->delete($whereclause,$numrows,false);
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
    public function insertlink($page_id,$action_id,$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $this->pageactiontable->clear();
        $this->pageactiontable->setfield("page_id",$page_id);
        $this->pageactiontable->setfield("action_id",$action_id);
        $success = $this->pageactiontable->insert(false);
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
// ==============================================================================================================
    public function getallactions(&$actions,&$numrows=0,$orderby="",$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        // called from fiewcontroller
        $actions = array();
        $success = $this->actiontable->selectall($actions,$numrows,$orderby,$trace);
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
    public function getallpageactions(&$actionpages,&$numrows,$orderby="",$trace=false){
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        // called from fiewcontroller
        $actionpages = array();
        $success = $this->pageactiontable->selectall($actionpages,$numrows,$orderby,$trace);
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
    public function pageisunrestricted($pagenum){
        if ($this->trace) { echo gtab()."Enter ".__METHOD__." page {$pagenum}<br>"; }
        return   $this->pages[$pagenum]["unrestricted"]??1;
     }

    public function getpagenames(): array {
        return array_map(fn($p) => $p['name'], $this->pages);
    }
}
