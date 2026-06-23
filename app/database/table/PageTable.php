<?php
namespace apptable;
use \lib\StdLib as lib;
class PageTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"pagenumber"=>"",
			"name" => "",
			"pagetype" => "",
			"unrestricted" => "",
			"maxcolumns" => "",
			"autoextendtasks" => ""
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

	public function getpagesforaction ($action_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT p.id as page_id, p.pagenumber as pagenumber, p.name as name FROM page p";
		$query .= " JOIN page_action pa ON p.id = pa.page_id ";
		$query .= " WHERE pa.action_id = {$action_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}


}
