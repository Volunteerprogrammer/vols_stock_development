<?php
namespace apptable;
use \lib\StdLib as lib;
class PageAccessTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"session_id"=>"",
			"page_num"=>"",
			"accesstime"=>"",
			"notes"=>"",
			"created"=>"");
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

    public function addrecord($session_id,$page_num,$notes) 
    {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $now = date('Y-m-d H:i:s');
        $this->fields = array("id"=>0,"session_id"=>$session_id,"page_num"=>$page_num,"accesstime"=>$now,"notes"=>$notes); // escaped in insert()
        $this->insert();
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>"; }
        return  $this->fields["id"];
    }

    public function addnote($note) 
    {
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $this->loadfromdb($this->fields["id"],$rows);
        if ($rows == 1) {
            $this->fields["notes"] .= $note; // escaped in put()
            $this->put("notes",$this->fields["notes"],true) ;
        }
        if ($this->trace ) { echo "Leave ".__METHOD__."<br>"; }
    }
    

}
