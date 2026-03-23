<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class LogManager 
{   
    private $trace=false;
    public function __construct(protected \apptable\SessionTable $sessiontable){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
    }
    public function init($session){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->db = $session->getdb();
        $this->sessiontable->init($this->db);
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
    }
    public function processdata($session){
        $this->session = $session;
        $data = $this->session->getrequestdata();
    }
   private function updatechildren($data,&$errormessage="",$trace=false) {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
    }


}
