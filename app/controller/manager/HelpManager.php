<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class HelpManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Help Content";
    protected $linkedobject = "";
    protected $db;
    public function __construct(protected \apptable\HelpContentTable $table) {}

    public function init($session, $trace=false) {
        parent::init($session);
    }

    protected function insertdataintotablefields($data) {
        $pid = ($data['page_id'] ?? '') !== '' ? (int)$data['page_id'] : null;
        parent::insertdataintotablefields($data);
        $this->table->setfield("page_id", $pid);
    }

    private function validatepageid(&$errormessage): bool {
        $data = $this->session->getrequestdata();
        $pid  = ($data['page_id'] ?? '') !== '' ? (int)$data['page_id'] : null;
        if ($pid === null) { return true; }
        if ($this->table->pageidisused($pid, (int)$this->id)) {
            $errormessage = "Page {$pid} already has a help record. Each page can only have one primary help record.";
            return false;
        }
        return true;
    }

    public function update(&$errormessage = "", $trace = false) {
        if (!$this->validatepageid($errormessage)) { return false; }
        return parent::update($errormessage, $trace);
    }

    public function insert(&$id = "0", &$errormessage = "", $trace = false) {
        if (!$this->validatepageid($errormessage)) { return false; }
        return parent::insert($id, $errormessage, $trace);
    }

    public function getblocks(array $record_ids, &$results, &$numrows): bool {
        return $this->table->getbyids($record_ids, $results, $numrows);
    }

    public function getbypage(int $page_id, &$results, &$numrows): bool {
        return $this->table->getbypage($page_id, $results, $numrows);
    }

    public function haspublishedhelp(int $pagenum): bool {
        return $this->table->haspublishedhelp($pagenum);
    }

    public function getforpages(array $page_ids, &$results, &$numrows): bool {
        return $this->table->getforpages($page_ids, $results, $numrows);
    }
}
