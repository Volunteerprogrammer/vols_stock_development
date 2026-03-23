<?php
namespace apptable;
use \lib\StdLib as lib;
class RolePageactionTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"pageaction_id"=>"",
			"role_id"=>"");
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

	public function getrolepageactionsforrole($roleid,&$records,&$numrows) {
		if ($this->trace ) { echo 'Enter '.__METHOD__."roleid $roleid<br>"; }
		$query = <<<QUERY
			SELECT * FROM role_pageaction WHERE role_id = {$roleid} 
		QUERY;
		$success= $this->query($query,$records,$numrows, false, false);
		if ($this->trace ) { echo 'Enter '.__METHOD__." query $query<br>"; }
		return $success;
	}

}