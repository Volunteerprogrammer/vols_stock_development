<?php
namespace apptable;
use \lib\StdLib as lib;
class RoleTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"name"=>"",
			"cellname"=>"",
			"rosterindex" => 0
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	 }
	public function getrolesfortasks ($task_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT r.id as id,r.name as name, tr.min_quantity as minqty, tr.max_quantity as maxqty, tr.waitlist as wl  FROM role r";
		$query .= " JOIN task_role tr ON r.id = tr.role_id ";
		$query .= " WHERE tr.task_id = {$task_id}";
		$success = $this->query($query,$results,$numrows,$trace);
		// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	 }
	public function getrolesforuser ($user_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT r.id as id,r.name as name FROM role r";
		$query .= " JOIN user_role ur ON r.id = ur.role_id ";
		$query .= " WHERE ur.user_id = {$user_id}";
		$success = $this->query($query,$results,$numrows,$trace);
		// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	 }
	public function getrolesforsession ($session_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT r.name FROM role r";
		$query .= " JOIN session_role sr ON r.id = sr.role_id ";
		$query .= " WHERE sr.session_id = {$session_id}";
		$success = $this->query($query,$results,$numrows,$trace);
		// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	 }
	public function getrolepageactionsforrole($rolepageaction_id,&$records,&$numrows) {
		$query = <<<QUERY
			SELECT *
			FROM role_pageaction 
			WHERE id = {$rolepageaction_id} ;
		QUERY;
		$success= $this->query($query,$records,$numrows, false, false);
		return $success;
	 }
	public function getrolesforpageaction ($session_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT r.name FROM role r";
		$query .= " JOIN session_role sr ON r.id = sr.role_id ";
		$query .= " WHERE sr.session_id = {$session_id}";
		$success = $this->query($query,$results,$numrows,$trace);
		// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	 }
	public function getrightsforuser($user_id,&$results,&$numrows = 0,$trace=false) {
		$query  = <<<QUERY
			SELECT DISTINCT CONCAT(p.pagenumber,"||",a.code) as r
				FROM user_role ur
				JOIN role_pageaction rpa 	ON rpa.role_id = ur.role_id 
				JOIN page_action pa 		ON pa.id = rpa.pageaction_id
				JOIN page p 				ON p.id = pa.page_id
				JOIN action a 				ON a.id = pa.action_id
				WHERE ur.user_id = '$user_id'; 
		QUERY;
		$success = $this->query($query,$results,$numrows,$trace);
	 }
}

