<?php
namespace apptable;
use \lib\StdLib as lib;
class HelpContentTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = [
            "id"          => "",
            "page_id"     => "",
            "title"       => "",
            "content"     => "",
            "also_covers" => "",
            "published"   => "",
            "pagetype"    => "",
        ];
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    protected function beforeinsert(&$fnames, &$fvalues) {
        $fnames  .= ", modified_by";
        $fvalues .= ", " . (int)$this->user_id;
    }

    protected function beforeupdate(&$set) {
        $set .= ", `modified_by` = " . (int)$this->user_id;
    }

    public function pageidisused(int $page_id, int $exclude_id = 0): bool {
        $tn    = lib::capsToUnderscores($this->tablename);
        $query = "SELECT id FROM {$tn} WHERE page_id = {$page_id} AND id != {$exclude_id} LIMIT 1";
        $this->query($query, $results, $numrows);
        return $numrows > 0;
    }

    public function getbyids(array $ids, &$results, &$numrows): bool {
        if (empty($ids)) { $results = []; $numrows = 0; return true; }
        $tn   = lib::capsToUnderscores($this->tablename);
        $list = implode(',', array_map('intval', $ids));
        $query = "SELECT * FROM {$tn} WHERE id IN ({$list})";
        return $this->query($query, $results, $numrows);
    }

    public function getbypage(int $page_id, &$results, &$numrows): bool {
        $tn    = lib::capsToUnderscores($this->tablename);
        $query = "SELECT * FROM {$tn} WHERE (page_id = {$page_id} OR FIND_IN_SET({$page_id}, also_covers) > 0) AND published = 1 LIMIT 1";
        return $this->query($query, $results, $numrows);
    }

    public function haspublishedhelp(int $page_id): bool {
        $tn    = lib::capsToUnderscores($this->tablename);
        $query = "SELECT id FROM {$tn} WHERE (page_id = {$page_id} OR FIND_IN_SET({$page_id}, also_covers) > 0) AND published = 1 LIMIT 1";
        $this->query($query, $results, $numrows);
        return $numrows > 0;
    }

    public function getforpages(array $page_ids, &$results, &$numrows): bool {
        if (empty($page_ids)) {
            $results = [];
            $numrows = 0;
            return true;
        }
        $tn   = lib::capsToUnderscores($this->tablename);
        $list = implode(',', array_map('intval', $page_ids));
        $query = "SELECT * FROM {$tn} WHERE (page_id IN ({$list})";
        foreach (array_map('intval', $page_ids) as $pid) {
            $query .= " OR FIND_IN_SET({$pid}, also_covers) > 0";
        }
        $query .= ") AND published = 1 ORDER BY page_id";
        return $this->query($query, $results, $numrows);
    }
}
