<?php
namespace apptable;
use \lib\StdLib as lib;
class UserRoleTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"user_id"=>"",
			"role_id"=>"");
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}

	public function getuserswithrole($roleid,&$records,&$numrows) {
		return $this->selectononefield("role_id", $roleid, $records,$numrows, false, false);
	}

}