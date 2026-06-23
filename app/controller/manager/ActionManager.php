<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ActionManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Action";
    protected $db;
    protected $linkedobject = ""; // we handle the links to the page table in the page form
    public function __construct(protected \apptable\ActionTable $table){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>"; }
    }
    protected function updatesetclause($data=[],$trace=false){
        $fields = array(
            "id"=>"",
            "name"=>"",
            "code"=>"",
            "page_type"=>"0"
        );        
        return $this->preparesetstatement($fields,$data);
    }
    protected function insertsetfields($data=[],$trace=false){
        $this->table->setfield("name",$data['name']);
        $this->table->setfield("code",$data['code']);
        $this->table->setfield("page_type",$data['page_type']??0);
    }

    public function getactionsforpage(int $page_id, &$results, &$numrows): bool {
        return $this->table->getactionsforpage($page_id, $results, $numrows);
    }

    public function getactionsforpagenumber(int $pagenumber, &$results, &$numrows): bool {
        return $this->table->getactionsforpagenumber($pagenumber, $results, $numrows);
    }
}
