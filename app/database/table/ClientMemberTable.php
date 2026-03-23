<?php
namespace apptable;
use \lib\StdLib as lib;
class ClientMemberTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"client_id"=>"",
			'name' =>"",
			'relationship' =>"",
			'month_of_birth'=>"",
			'year_of_birth'=>"",
			'country_of_birth' =>"",
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	protected function beforeinsert(&$fnames,&$fvalues){
        $fnames .= ",registered_by";
        $fvalues .= ",".$this->user_id;
	}
	protected function beforeupdate(&$set){
		$set .= ", `modified_by` = ".$this->user_id;
	}
}