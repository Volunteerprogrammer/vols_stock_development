<?php
namespace apptable;
use \lib\StdLib as lib;
class EmailTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"senddate"=>"",
			"status"=>"",
			"email"=>"",
			"response"=>""
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

	public function getemailsforuser ($user_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT e.id as email_id,e.status,e.email,e.response,ue.MessageUUID,ue.MessageID,ue.MessageHref FROM email e";
		$query .= " JOIN email_user ue ON e.id = ue.email_id ";
		$query .= " WHERE ue.user_id = {$user_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}
	public function getemailsforsession ($session_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT e.id  as email_id,e.status,e.email,e.response FROM email e";
		$query .= " JOIN email_session se ON e.id = se.email_id ";
		$query .= " WHERE se.session_id = {$session_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}
}
