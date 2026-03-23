<?php
namespace app\controller\manager;

class ActionManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Event";
    protected $childkey = "session";
    protected $linkedobject = ""; // we handle the links to the page table in the page form
    public function __construct(protected \database\table\ActionTable $table){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
    }

    protected function updatesetclause(){
        $set  = " `name` = '{$this->requestdata['name']}'";
        $set .= ", `code` = '{$this->requestdata['code']}'";
        return $set;
    }
    protected function insertsetfields(){
        $this->table->setfield("name",$this->requestdata['name']);
        $this->table->setfield("code",$this->requestdata['code']);
    }
}
