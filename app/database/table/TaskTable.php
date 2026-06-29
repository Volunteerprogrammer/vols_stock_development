<?php
namespace apptable;
use \lib\StdLib as lib;
class TaskTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		// The ordinal position of each field in this array is 'known' by the form in that
		// each HTML input field has a data-attribute carrying the ordinal position of its data field
		// which is used to load/read the data into/from the fields.
		// !!!   so do not change this array without updating the form !!!
		$this->fields = array(
			"id" => "",
			"page_id" => "",  			//1
			"name" => "",
			"starttime" => "",
			"endtime" => "",    		//4
			"recurrence" => "",   		//5
			"dailyoption" => "",		//6
			"dailyinterval" => "",		//7
			"weeklyinterval" => "",		//8
			"weeklydow" => "",			//9
			"monthlyoption" => "",  	//10
			"monthlydayofmonth" => "",	//11
			"monthlyinterval0" => "",	//12
			"monthlywhichdow" => "",	//13
			"monthlydow" => "",			//14
			"monthlyinterval1" => "",  	//15
			"yearlyoption" => "",		//16
			"yearlydom" => "",			//17
			"yearlymonth0" => "",		//18
			"yearlywhichdom" => "",		//19
			"yearlywhichday" => "",  	//20
			"yearlymonth1" => "",		//21
			"taskgroup" => "",			//22
			"groupindex" => "",			//23
			"cellsperrow" => "", 		//24
			"weeklyindex" => "",		//25
			"isfunction" => "",			//26
			"logattendance" => ""		//27
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	public function gettasksforpage ($page_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT t.id as task_id ,t.name FROM task t";
		$query .= " WHERE t.page_id = {$page_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v(__METHOD__ => "",$results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}

}
