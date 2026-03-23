<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class TaskExtenderManager {
    protected $trace= false;   
    protected $report ="";
    protected $today;
    protected $dateformatstr = 'd-m-Y';
    protected $todaydow;
    protected $nl = "<BR>\n";
    public function __construct(protected \app\controller\manager\SessionManager $sessionmanager) {
        date_default_timezone_set('Australia/Melbourne');
     }
    public function init($session) {  
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        try {
            $this->sessionmanager->init($session);
        } catch (\Exception $e) {
            die('Caught exception in  TaskExtenderManager->init() : '.$e->getMessage());
        }
     }
//==================================================================PUBLISH
    public function extendsessions($task_id,$errorhandler,$context="",$trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $this->today = new \DateTimeImmutable();
        $this->todaydow = $this->today->format("w");
        $success = $this->sessionmanager->sessiontablequery("SELECT  * FROM task;",$tasks,$numrows,$trace);
        if ($success) {
            foreach ($tasks as $task) {
               if ($task_id == 0 || $task["id"] == $task_id) {
                   switch ($task["recurrence"]) {
                        case "Once-only": $this->extendonceonlytask($task);break;
                        case "Daily"    : $this->extenddailytask($task);break;
                        case "Weekly"   : $this->extendweeklytask($task);break;
                        case "Monthly"  : $this->extendmonthlytask($task);break;
                        // case "Yearly"   : $this->yearly($task);break;
                        default:
                    }
                } 
            }
        }
        if ($this->report !== "") {
            $this->report = date('Y-m-d H:i:s').": running publish(){$this->nl}".$this->report.date('Y-m-d H:i:s').": leaving publish(){$this->nl}";
            $errorhandler->applicationlog($this->report,$context);
        }
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $this->report;
     }
    private function extendonceonlytask($task) {
        $taskday = new \DateTimeImmutable($task["startdate"]);
        return $this->sessionmanager->generatepublish($task,$taskday,$taskday,$this->report);
     } 
    private function dowbitstr($taskday){
        $thisdow = pow(2,(int) $taskday->format("w"));    
        return str_pad(decbin($thisdow),7,"0",STR_PAD_LEFT);        
     }
    private function extenddailytask($task) {
        $startdate = new \DateTimeImmutable($task["startdate"]);
        $diff = $startdate->diff($this->today) ;
        $diff = $diff->format('%R%a') ;
        $createweeks = $task["leadtime"];
        $publishweeks = $task["publishedleadtime"];
        $success = true;
        if ($task["dailyoption"] == 0) { // every x daya from ...
            if (lib::validateDate($task["startdate"],"Y-m-d") && $task["dailyinterval"] > 0) {
                $period = $task["dailyinterval"];
                if ($diff > 0) { //then startdate is in the past (otherwise just start on $startdate)
                    $mod = ($diff + $period) % $period; // may be zero
                    $taskday = $this->today->modify("+{$mod} days ");
                } else  {
                    $taskday =  clone $startdate;
                }
                $lastpublishedday = $taskday->modify("+{$publishweeks} weeks ");
                $lastcreateday = $taskday->modify("+{$createweeks} weeks ");
                while ($success && ($taskday <= $lastcreateday) ) {
                    $this->sessionmanager->generatepublish($task,$taskday,$lastpublishedday,$this->report);
                    $taskday = $taskday->modify("+{$period} days ");
                }
            }
        } else { // weekdays
            $taskday = $this->today;
            $lastpublishedday = $taskday->modify("+{$publishweeks} weeks ");
            $lastcreateday = $taskday->modify("+{$createweeks} weeks ");
            $weekdays = "0111110";
            if ($diff > 0) { //then startdate is in the past (otherwise just start on $startdate)
                $taskday = $this->today;
            } else  {
                $taskday = $startdate;
            }
            while ($success && ($taskday <= $lastcreateday) ) {
                $thisdow =  $this->dowbitstr($taskday);
                if (($thisdow & $weekdays) != 0) {
                    $this->sessionmanager->generatepublish($task,$taskday,$lastpublishedday,$this->report);
                }
                $taskday = $taskday->modify("+1 day");
            }
        }
     } 


     public static function determinefirstdateofweeklyperiod ($startday,$targetdate,$weeksinperiod) {
        // $startday is the date that the (potentially) multi-week recurrence began (property of the task)  
        // the target date might be in any week within the multi-week recurring period
        // this function calculates the date of the (Sun)day commencing the multi-week period that contains the target date
        // $startday assumed to be a string in the format Y-m-d
        // $targetdate assumed to be a DateTimeImmutable() obj
        $periodstartday = new \DateTime($startday);
        // return $periodstartday to a Sunday
        while ($periodstartday->format("w")>0) {
            $periodstartday->modify("-1 day");
        }
        $i = 0;
        // calc $diff = total days between $periodstartday and targetdate
        $diff = $periodstartday->diff($targetdate);
        // calc $perioddays = the number of days in the period (base 0)
        $perioddays = ((7*$weeksinperiod)-1);
        // iterate $periodstartday forward by $weeksinperiods until the period contains $targetdate
        while ((int) $diff->format('%a') > $perioddays  ) { //&& $i++ < 250 
            $periodstartday->modify("+{$weeksinperiod} weeks");
            $diff = $periodstartday->diff($targetdate);
        }
        return $periodstartday;
     }
    private function extendweeklytask($task, $trace = false ) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__." Task {$task["name"]} <br>";} 
        $taskdows = substr("0000000".decbin($task["weeklydow"]),-7);// decbin gives string representation of input so 19 = :"10011"
        // $taskdows is in high-bit-first order. We need to reverse that;
        $taskdows = strrev($taskdows);
        $leadtimeweeks = $task["leadtime"];
        $publishweeks = $task["publishedleadtime"];
        // $periodstart is the first day of a weekinterval containing an task. They start on startdate and  are $task["weeklyinterval"] weeks apart
        $periodstartday = self::determinefirstdateofweeklyperiod ($task["startdate"],$this->today,$task["weeklyinterval"]);
        $success = true;
        $taskday = new \DateTime();
        for ($i=0;$i<=6;$i++) { // try all dows
            if (substr($taskdows,$i,1) == "1") { // task occurs on this dow
                $taskday = $periodstartday; 
                $advance = $i + (7*$task["weeklyindex"]);// find the task day in the appropriate week within the weeklyintervaly 
                $lastpublishedday = clone $lastcreateday = clone $taskday->modify("+{$advance} days "); 
                $lastcreateday->modify("+{$leadtimeweeks} weeks ");
                $lastpublishedday->modify("+{$publishweeks} weeks ");
                $errormessage = "";
        // lib::pr(" DOW = {$i}   {$taskday->format($this->dateformatstr)}   {$lastcreateday->format($this->dateformatstr)}   {$lastpublishedday->format($this->dateformatstr)}");
                while ($success && ($taskday <= $lastcreateday) ) {
        // lib::pr("{$taskday->format($this->dateformatstr)}  <  {$lastcreateday->format($this->dateformatstr)}");
                    $success = $this->sessionmanager->generatepublish($task,$taskday,$lastpublishedday,$this->report);
                    $taskday->modify("+{$task["weeklyinterval"]} weeks ");
                }
            }
        }
     } 
    private function extendmonthlytask($task) {
        $startdate = new \DateTimeImmutable($task["startdate"]);
        $success = true;
        if (lib::validateDate($task["startdate"],"Y-m-d")) {
            //$p0 is the date-based recurrence (e.g. 4th of every month). $p1 is the dow-based option (e.g. third Tuesday of every month)
            $p0 = (($task["monthlyoption"] == 0) && ($task["monthlydayofmonth"] > 0) && ($task["monthlydayofmonth"] < 32) && ($task["monthlyinterval0"] > 0));  
            $p1 = (($task["monthlyoption"] == 1) && ($task["monthlyinterval1"] > 0)); 
            if  ($p0 || $p1) { // we have a selection
                $createweeks = $task["leadtime"];
                $publishweeks = $task["publishedleadtime"];
                $period = $p0?$task["monthlyinterval0"]:$task["monthlyinterval1"];
                $taskday = $startdate;
                while ($taskday->format("Ym") < $this->today->format("Ym")) {
                    $taskday = $taskday->modify("+{$period} months");
                }
                if ($p0) {
                    $datethismonth = new \DateTimeImmutable("{$taskday->format("Y-m-")}{$task["monthlydayofmonth"]}");
                } else {
                    $datethismonth = $this->addmonths($this->today,$task["monthlywhichdow"],$task["monthlydow"],0); 
                }
                if ($datethismonth > $taskday) { 
                   $taskday = $datethismonth;
                }
        // lib::pr("a",$startdate,$taskday,$datethismonth);
                $e = 0;
                $lastpublishedday = $taskday->modify("+{$publishweeks} weeks ");
                $lastcreateday = $taskday->modify("+{$createweeks} weeks ");
                while ($success && ($taskday <= $lastcreateday) ) {
                    $e++;
                    if ($e>50) {break;}
                    if ($taskday >= $startdate) {
        // lib::pr("b",$taskday);
                        $success = $this->sessionmanager->generatepublish($task,$taskday,$lastpublishedday,$this->report);
                    }
                    if ($p0) {
                        $taskday = $taskday->modify("+{$period} months ");
                    } else {
                        $newdate = $this->addmonths($taskday,$task["monthlywhichdow"],$task["monthlydow"],$period); 
                        unset($taskday);
                        $taskday = $newdate;
        // echo $e." : ".$newdate->format("d/m/Y")." < ".$lastcreateday->format("d/m/Y")." // ".$lastpublishedday->format("d/m/Y")."<br><br>";
                    }
                 }
            }
    
        }
     } 
    private function addmonths($taskday,$whichidx,$dowidx,$period) {
        $which = [0=>"first",1=>"second",2=>"third",3=>"fourth",4=>"last"][$whichidx];
        $dayofmonth = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"][$dowidx];
        $newday =  $taskday->modify("+{$period} months");
        return $newday->modify("{$which} {$dayofmonth} of {$newday->format("F Y")}");
     }
}