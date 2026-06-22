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
            "id"                => "",
            "page_id"           => "",
            "title"             => "",
            "content"           => "",
            "date_registered"   => "",
            "registered_by"     => "",
            "date_last_updated" => "",
            "modified_by"       => "",
            "also_covers"       => "",
        ];
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
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
        $query = "SELECT * FROM {$tn} WHERE page_id = {$page_id} OR FIND_IN_SET({$page_id}, also_covers) > 0 LIMIT 1";
        return $this->query($query, $results, $numrows);
    }

    public function getforpages(array $page_ids, &$results, &$numrows): bool {
        if (empty($page_ids)) {
            $results = [];
            $numrows = 0;
            return true;
        }
        $tn   = lib::capsToUnderscores($this->tablename);
        $list = implode(',', array_map('intval', $page_ids));
        // Match primary page_id OR any page in also_covers
        $query = "SELECT * FROM {$tn} WHERE page_id IN ({$list})";
        foreach (array_map('intval', $page_ids) as $pid) {
            $query .= " OR FIND_IN_SET({$pid}, also_covers) > 0";
        }
        $query .= " ORDER BY page_id";
        return $this->query($query, $results, $numrows);
    }
}
