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
    public function getallrecords(&$data, $orderby, &$parents, &$numrows, $trace=false, $active=false) {
        $order = $orderby ? "r.{$orderby}" : "r.name";
        $this->table->query(
            "SELECT r.id, r.name, r.maxcolumns, r.autoextendtasks, r.leadtime,
                    r.publishedleadtime, r.startdate, r.enddate, r.sessiondepth,
                    p.pagenumber
             FROM roster r
             JOIN page p ON p.id = r.id
             ORDER BY {$order}",
            $data, $numrows
        );
        return true;
     }
    public function insert(&$id="0", &$errormessage="", $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        $name = trim($data["name"] ?? "");
        if ($name === "") {
            $errormessage = "Please enter a roster name.";
            return false;
        }
        $roster_id = $this->createnewrosterpage($name, $errormessage);
        if (!$roster_id) { return false; }
        $nulldate = fn($v) => (trim((string)$v) === '') ? null : $v;
        $sql = "INSERT INTO roster
                    (id, name, maxcolumns, autoextendtasks, leadtime, publishedleadtime, startdate, enddate, sessiondepth)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $roster_id,
            $name,
            $data["maxcolumns"]        ?: null,
            (int)($data["autoextendtasks"] ?? 0),
            $data["leadtime"]          ?: null,
            $data["publishedleadtime"] ?: null,
            $nulldate($data["startdate"] ?? ""),
            $nulldate($data["enddate"]   ?? ""),
            $data["sessiondepth"]      ?: null,
        ];
        $success = $this->table->execute_params($sql, $params, $result, $numrows, $errormessage, 1, $trace);
        if ($success) {
            $id = (string)$roster_id;
            $this->session->putrequestid($id);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    public function update(&$errormessage="", $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $data      = $this->session->getrequestdata();
        $roster_id = (int)($data["id"]   ?? 0);
        $new_name  = trim($data["name"]  ?? "");
        $success   = parent::update($errormessage, $trace);
        if ($success && $roster_id > 0 && $new_name !== "") {
            $this->pagetable->setfield("id",   $roster_id);
            $this->pagetable->setfield("name", $new_name, true, $errormessage);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    public function delete(&$errormessage="", $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $data      = $this->session->getrequestdata();
        $roster_id = (int)($data["id"] ?? 0);
        $success   = parent::delete($errormessage, $trace);
        if ($success && $roster_id > 0) {
            $whereclause = "id = '{$roster_id}'";
            $this->pagetable->delete($whereclause, $numrows, false);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    private function createnewrosterpage($name, &$errormessage) {
        // Reuse an existing unassigned roster page with this name (e.g. from a prior partial save)
        $esc = $this->pagetable->real_escape_string($name);
        $this->pagetable->query(
            "SELECT id FROM page WHERE name = '{$esc}' AND pagetype = 2 AND id NOT IN (SELECT id FROM roster) LIMIT 1",
            $existing, $existing_count
        );
        if ($existing_count > 0) {
            return (int)$existing[0]["id"];
        }
        // Auto-number: next available pagenumber for pagetype=2 pages
        $this->pagetable->query(
            "SELECT COALESCE(MAX(pagenumber), 100) + 1 AS next_num FROM page WHERE pagetype = 2",
            $result, $numrows
        );
        $next_num = (int)($result[0]["next_num"] ?? 101);
        $this->pagetable->clear();
        $this->pagetable->setfield("pagenumber",   $next_num);
        $this->pagetable->setfield("name",         $name);
        $this->pagetable->setfield("pagetype",     2);
        $this->pagetable->setfield("unrestricted", 0);
        $success = $this->pagetable->insert(true, $new_page_id, false, $errormessage);
        return $success ? (int)$new_page_id : 0;
     }
}
