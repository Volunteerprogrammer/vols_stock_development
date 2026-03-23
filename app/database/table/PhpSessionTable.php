<?php
namespace apptable;
use \lib\StdLib as lib;

class PhpsessionTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"user_id"=>"",
			"phpsession"=>"",
			"isadmin"=>"",
			"starttime"=>"",
			"last_access"=>"",
			"client_ip"=>"",
			"timezone"=>"",
			"created"=>"",
			"locktime"=>"",
			"lockedby"=>"");
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
}
