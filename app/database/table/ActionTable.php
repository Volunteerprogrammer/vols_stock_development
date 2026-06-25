<?php
namespace apptable;
use \lib\StdLib as lib;
class ActionTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"name"=>"",
			"code"=>"",
			"page_type"=>""
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

	public function getactionsforpage ($page_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT a.id as action_id,a.name,a.code FROM action a";
		$query .= " JOIN page_action pa ON a.id = pa.action_id ";
		$query .= " WHERE pa.page_id = {$page_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}

	public function getactionsforpagenumber(int $pagenumber, &$results, &$numrows = 0): bool {
		$query  = "SELECT a.id as action_id,a.name,a.code FROM action a";
		$query .= " JOIN page_action pa ON a.id = pa.action_id";
		$query .= " JOIN page p ON pa.page_id = p.id";
		$query .= " WHERE p.pagenumber = {$pagenumber}";
		return $this->query($query, $results, $numrows);
	}

	public function getactionsforpagetype(int $pagetype, &$results, &$numrows = 0): bool {
		$query  = "SELECT DISTINCT a.name, a.code FROM action a";
		$query .= " WHERE a.page_type IN (0, {$pagetype}) ORDER BY a.name";
		return $this->query($query, $results, $numrows);
	}

}
