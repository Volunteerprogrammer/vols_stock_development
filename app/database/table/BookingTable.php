<?php
namespace apptable;
use \lib\StdLib as lib;
class BookingTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"user_id"=>"",
			"session_role_id"=>"",
			"status"=>"",
			"booked_time"=>"",
			"booked_by"=>"",
			"deleted_time"=>"",
			"deleted_by"=>""
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
}
