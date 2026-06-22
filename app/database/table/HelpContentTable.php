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
        ];
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    public function getbypage(int $page_id, &$results, &$numrows): bool {
        $query = "SELECT * FROM ".lib::capsToUnderscores($this->tablename)." WHERE page_id = {$page_id}";
        return $this->query($query, $results, $numrows);
    }

    public function getforpages(array $page_ids, &$results, &$numrows): bool {
        if (empty($page_ids)) {
            $results = [];
            $numrows = 0;
            return true;
        }
        $list  = implode(',', array_map('intval', $page_ids));
        $query = "SELECT * FROM ".lib::capsToUnderscores($this->tablename)." WHERE page_id IN ({$list}) ORDER BY page_id";
        return $this->query($query, $results, $numrows);
    }
}
