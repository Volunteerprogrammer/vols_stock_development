<?php
namespace database\table;
use \lib\StdLib as lib;
class EventTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);

		// The ordinal position of each field in this array is 'known' by the form inj that
		// each HTML input field has a data-attribute carrying the ordinal position of its data field
		// which is used to load/read the data into/from the fields.
		// !!!   so do not change this array without updating the form !!!
		$this->fields = array(
			"id" => "",
			"page_id" => "",  			//1
			"name" => "",
			"starttime" => "",
			"endtime" => "",    		//4
			"leadtime" => "",			//5
			"publishedleadtime" => "",	//6
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
			"startdate" => "",			//24
			"enddate" => "",			//25
			"eventgroup" => "",			//26
			"groupindex" => "",			//27
			"cellsperrow" => "", 		//28
			"sessiondepth" => "",		//29
			"weeklyindex" => "",		//30
			"isfunction" => "",			//31
			"logattendance" => ""		//32
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	public function geteventsforpage ($page_id,&$results,&$numrows = 0,$trace=false) {
		if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
		$query  = "SELECT e.id as event_id ,e.name FROM event e";
		$query .= " WHERE e.page_id = {$page_id}";
		$success = $this->query($query,$results,$numrows,$trace);
// lib::v(__METHOD__ => "",$results);
		if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>";}
		return $success;
	}

}
