<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ReportManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Report";
    protected $db;
    protected $linkedobject = ""; // we handle the links to the page table in the page form
    public function __construct(protected \apptable\ReportTable $table){
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>";}
    }
    public function generatereport($query,&$results,&$numrows,$trace=false){
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $success = true;
        if (preg_match('/(?:DELETE|UPDATE|INSERT)/i',$query)) {
            $results = [["NOTICE"],["This query contained one or more illegal words."],["Execution is not permitted"]];
        } else {
            $success = $this->table->query($query,$results,$numrows,$trace);
        }
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>";}
        return $success;
    }
}
