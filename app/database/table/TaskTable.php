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
			"bookingalertlevels" => "",	//5 (moved from roster: leadtime/publishedleadtime removed)
            "bookingalertperiods" => "",//6
			"recurrence" => "",   		//7
			"dailyoption" => "",		//8
			"dailyinterval" => "",		//9
			"weeklyinterval" => "",		//10
			"weeklydow" => "",			//11
			"monthlyoption" => "",  	//12
			"monthlydayofmonth" => "",	//13
			"monthlyinterval0" => "",	//14
			"monthlywhichdow" => "",	//15
			"monthlydow" => "",			//16
			"monthlyinterval1" => "",  	//17
			"yearlyoption" => "",		//18
			"yearlydom" => "",			//19
			"yearlymonth0" => "",		//20
			"yearlywhichdom" => "",		//21
			"yearlywhichday" => "",  	//22
			"yearlymonth1" => "",		//23
			"taskgroup" => "",			//24
			"groupindex" => "",			//25
			"cellsperrow" => "", 		//26
			"weeklyindex" => "",		//27
			"isfunction" => "",			//28
			"logattendance" => ""		//29
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
