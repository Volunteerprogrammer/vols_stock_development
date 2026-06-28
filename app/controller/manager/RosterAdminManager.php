<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class RosterAdminManager extends \fw\controller\manager\StdManager {
    private $trace = false;
    protected $db;
    protected $name = "Roster";
    protected $linkedobject = "";

    public function __construct(protected \apptable\RosterTable  $table,
                                protected \apptable\PageTable    $pagetable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function init($session, $trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->table->init($this->db);
        $this->pagetable->init($this->db);
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    protected function getparents(&$parents, $trace=false) {
        // roster pages (pagetype=2) that do not yet have a roster record
        $query = "SELECT p.id, p.name FROM page p
                  WHERE p.pagetype = 2
                  AND p.id NOT IN (SELECT id FROM roster)
                  ORDER BY p.name";
        $success = $this->table->query($query, $parents, $numrows, $trace);
        return $success;
     }
    public function insert(&$id="0", &$errormessage="", $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        $page_id      = (int)($data["page_id"]      ?? 0);
        $new_page_name = trim($data["new_page_name"] ?? "");
        if ($page_id > 0) {
            $roster_id = $page_id;
        } elseif ($new_page_name !== "") {
            $roster_id = $this->createnewrosterpage($new_page_name, $errormessage);
            if (!$roster_id) { return false; }
        } else {
            $errormessage = "Please select an existing page or enter a new page name.";
            return false;
        }
        $this->insertdataintotablefields($data);
        $this->table->setfield("id", $roster_id);
        $success = $this->table->insert(true, $id, $trace, $errormessage);
        if ($success) {
            $this->session->putrequestid($id);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    private function createnewrosterpage($name, &$errormessage) {
        // Find next available page number for roster pages
        $query = "SELECT COALESCE(MAX(pagenumber), 100) + 1 AS next_num FROM page WHERE pagetype = 2";
        $this->pagetable->query($query, $result, $numrows);
        $next_num = (int)($result[0]["next_num"] ?? 101);
        // Insert the new page
        $this->pagetable->clear();
        $this->pagetable->setfield("pagenumber",  $next_num);
        $this->pagetable->setfield("name",        $name);
        $this->pagetable->setfield("pagetype",    2);
        $this->pagetable->setfield("unrestricted", 0);
        $success = $this->pagetable->insert(true, $new_page_id, false, $errormessage);
        return $success ? (int)$new_page_id : 0;
     }
}
