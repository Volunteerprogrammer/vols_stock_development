<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class TaskTypeManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Task Type";
    public function __construct(protected \apptable\TaskTypeTable $table){
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
