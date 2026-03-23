<?php
namespace apptable;
use \lib\StdLib as lib;
class PageActionTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"page_id"=>"",
			"action_id"=>"");
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	public function getpageaction($pageaction_id,&$records,&$numrows) {
		$query = <<<QUERY
			SELECT pa.id as id, CONCAT(p.name,":  ",a.name) as name 
			FROM page_action pa
			JOIN page p ON p.id = pa.page_id
			JOIN action a ON a.id = pa.action_id
			WHERE pa.id = {$pageaction_id} ;
		QUERY;
		$records = [];
		$success= $this->query($query,$records,$numrows, false, false);
		if ($numrows) {
			$records = $records[0];
		}
		return $success;
	}
	public function getallpageactions(&$records,&$numrows,$trace) {
		$query = <<<QUERY
			SELECT pa.id as id, CONCAT(p.name,"&nbsp; --- &nbsp;",a.name) as name, a.name as actionname, p.id as pageid 
			FROM page_action pa
			JOIN page p ON p.id = pa.page_id
			JOIN action a ON a.id = pa.action_id
			ORDER BY p.pagenumber, a.name;
		QUERY;
		$success= $this->query($query,$records,$numrows, false, false);
		return $success;
	}
	public function getpageactionsforrole ($role_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query = <<<QUERY
			SELECT pa.id as id, CONCAT(p.name,"&nbsp; --- &nbsp;",a.name) as name 
			FROM page_action pa
			JOIN page p ON p.id = pa.page_id
			JOIN action a ON a.id = pa.action_id
			JOIN role_pageaction rpa ON pa.id = rpa.pageaction_id
			WHERE rpa.role_id = {$role_id};
		QUERY;
		$success= $this->query($query,$records,$numrows, false, false);
// lib::v($results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}
}