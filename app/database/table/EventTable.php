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
			"endtime" => "",    	
			"leadtime" => "",			//5
			"publishedleadtime" => "",
			"bookingalertlevels" => "",
            "bookingalertperiods" => "",
			"recurrence" => "",   
			"dailyoption" => "",		//10
			"dailyinterval" => "",
			"weeklyinterval" => "",
			"weeklydow" => "",
			"monthlyoption" => "",  
			"monthlydayofmonth" => "",	// 15
			"monthlyinterval0" => "",
			"monthlywhichdow" => "",
			"monthlydow" => "",
			"monthlyinterval1" => "",  
			"yearlyoption" => "",		//20
			"yearlydom" => "",
			"yearlymonth0" => "",
			"yearlywhichdom" => "",
			"yearlywhichday" => "",  
			"yearlymonth1" => "",		//25
			"startdate" => "",
			"enddate" => "",
			"eventgroup" => "",
			"groupindex" => "",
			"cellsperrow" => "", 		//30
			"sessiondepth" => "",		
			"weeklyindex" => "",			//32
			"isfunction" => "",
			"logattendance" => ""			//34
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
