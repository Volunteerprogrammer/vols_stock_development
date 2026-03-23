<?php
namespace apptable;
use \lib\StdLib as lib;
class MenuitemTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"menucode"=>"",
			"page_number" => "",
			"text" => "",
			"inactive" => "",
			"menu_number" => '',
			"is_public" => ''
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
}


