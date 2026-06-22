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
        $this->table->init($this->db);
    }

    protected function updatesetclause($data=[], $trace=false) {
        $fields = [
            "id"                => "",
            "page_id"           => "",
            "title"             => "",
            "content"           => "",
            "date_last_updated" => date("Y-m-d H:i:s"),
            "modified_by"       => $this->user_id,
        ];
        return $this->preparesetstatement($fields, $data);
    }

    protected function insertsetfields($data=[], $trace=false) {
        $this->table->setfield("page_id",           $data['page_id']  ?? 0);
        $this->table->setfield("title",             $data['title']    ?? '');
        $this->table->setfield("content",           $data['content']  ?? '');
        $this->table->setfield("date_registered",   date("Y-m-d H:i:s"));
        $this->table->setfield("registered_by",     $this->user_id);
        $this->table->setfield("date_last_updated", date("Y-m-d H:i:s"));
        $this->table->setfield("modified_by",       $this->user_id);
    }

    public function getbypage(int $page_id, &$results, &$numrows): bool {
        return $this->table->getbypage($page_id, $results, $numrows);
    }

    public function getforpages(array $page_ids, &$results, &$numrows): bool {
        return $this->table->getforpages($page_ids, $results, $numrows);
    }
}
