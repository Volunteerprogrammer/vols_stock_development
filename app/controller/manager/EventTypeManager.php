<?php
namespace app\controller\manager;

class EventTypeManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Event Type";
    public function __construct(protected \database\table\EventTypeTable $table){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
    }
    protected function updatesetclause(){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $set = " `name` = '".$this->requestdata['name']."'";
        return $set;
    }
    protected function insertsetfields(){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $this->table->setfield("name",$this->requestdata['name']);
    }    

    
}
