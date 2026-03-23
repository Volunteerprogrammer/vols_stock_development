<?php
namespace fw\database\table;
use \lib\StdLib as lib;
abstract class MySQLTable  extends Table
{
    private $trace= false;      
    protected $dbquote = '`';
    protected $user_id;  
    public function __construct(){
        $this->databaselocktimeoutsecs = 1800;
     }
    public function init($db,$user_id="null"){
        // if ($this->trace ) { echo 'Enter '.__METHOD__."(".$this->tablename.')<br>'; }
        $this->user_id = $user_id;
        $this->db = $db;
        // need to remove the namespace from fully qualified tablename
        $classname = get_class($this);
        $this->tableclassname = lib::getLastToken($classname,"\\");
        // remove the trailing "Table" in the Classname for the tablename
        $this->tablename = substr($this->tableclassname,0,strlen($this->tableclassname)-5);
        unset ($this->fields);
        if ( $this->trace ) { echo 'Leave '.__METHOD__.": {$classname} >> {$this->tableclassname} >> {$this->tablename} <br>"; }
     }
    public function setuser($user_id){
        $this->user_id = $user_id;
    }
    public function real_escape_string($str){
        return $this->db->real_escape_string($str);
     } 
    public function insert($recoverid=true,&$id=0, $trace=false,&$errormessage='') 	{ //, $trace=false
        //assumes an ID field exists and is an autoincrement field and will be assigned by the INSERT
        if ($this->trace|| $trace  ) { echo gtab(1)."Enter ".__METHOD__."(".$this->tablename.")<br>\n"; }
        try {
            $fvalues = $fnames = $comma = "";
            foreach ($this->fields as $var=>$val) {
                if (!(strtoupper($var) == "ID" || strtoupper($var) == 'LOCKTIME' || strtoupper($var) == 'LOCKEDBY'|| strtoupper($var) == 'CREATED')) {
                    if (!is_null($val)) {
                        $fnames .= $comma.$var;
                        $fvalues .= $comma.((strtolower($val)=="null") ? 'null' : ("'".$this->db->real_escape_string($val)."'"));
                        $comma = ", ";
                    }
                } else if (strtoupper($var) == 'CREATED') {
                        $fnames .= $comma.$var;
                        $fvalues .= $comma."'".lib::nowf()."'";
                }
            }
            $this->beforeinsert($fnames,$fvalues);
            $query = "INSERT INTO ".lib::capsToUnderscores($this->tablename)." (".$fnames.") VALUES (".$fvalues.")";
        //  lib::e($query);
            $success = ($this->db->dbquery($query, $this->result, $numrows,$errormessage, 1,1,$matchedrows,$trace));
            if ($success && isset($this->fields["id"]) && $recoverid) {
                $id = $this->result;
                $this->fields["id"] = $this->result;
            }
        } catch (\Exception $e) {
            die(__METHOD__." : ".$e->getMessage());
        }
        if ($this->trace || $trace  ) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."Success = ".$success."<br>$errormessage\n"; }
        return $success;
     }
    protected function getvalidlockstart($now) 	{
        $interval = 'PT'.$this->databaselocktimeoutsecs .'S';
        $firstvalidlockstart = $now->sub(new \DateInterval($interval));
        return $firstvalidlockstart ;
     }
    public function update($set_clause, $whereclause, &$numrows,&$errormessage, $trace=false, &$matchedrows=0, $keeplock=false) 	{
        if ($this->trace|| false ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__." SET ".$set_clause." WHERE ".$whereclause."<br>\n"; }
        // lib::v($this->tablename,$set_clause, $whereclause);
        if ($this->ismyfield('lockedby') && (strpos($set_clause,'lockedby')===false))  {
            if (!$keeplock) {
                $set_clause .= (empty($set_clause)?'':', ').$this->dbquote."lockedby".$this->dbquote." = '', ".$this->dbquote."locktime".$this->dbquote." = ''";
            }else{ 
                $set_clause .= (empty($set_clause)?'':', ').$this->dbquote."lockedby".$this->dbquote."='".session_id()."', ".$this->dbquote."locktime".$this->dbquote." = '".lib::nowf()."'";
            } 
        }   
        $this->beforeupdate($set_clause);
        $query = "UPDATE ".lib::capsToUnderscores($this->tablename)." SET ".$set_clause;

        $query .= empty($whereclause)? "" : (" WHERE ".$whereclause); 
        $success =  $this->db->dbquery($query, $this->result, $numrows,$errormessage, 1, false, $matchedrows, $trace);
        // lib::pr($query,$success,$this->result,$numrows,$errormessage);
        if ($this->trace|| $trace) { echo gtab(-1).$this->tablename.":".__METHOD__." >> ".$numrows."/".$matchedrows." rows <br><br>\n"; }
        // if($this->tablename=='User') {        
        //     sleep(1);
        //     $this->selectonID(1,$user);
        //     lib::v($user);        
        // }
        return $success;
     }
    public function updateallfields(&$numrows,&$errormessage, $keeplock=false, $trace=false) 	{ 
        // assumes $fields is loaded with required values
        if ($this->trace|| $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        global $siteglobals,$now;
        $success = false;
        if (!empty($this->fields['id'])) { 
            $comma = '';
            $setclause = '';
            foreach ($this->fields as $field=>$val) {
              if (!(strtoupper($field) == 'CREATED')) {  
                if (strtoupper($field) == 'ID') { // we don't update the ID field but use it for the whereclause
                    if (isset($this->fields['lockedby'])) { //need to include a lock test in the where clause - OK if we have the lock, or noone has a lock, or it's expired
                        $curlockstart = $this->getvalidlockstart($now);
                        $whereclause= "ID = '".$this->db->real_escape_string($this->fields['id'])."' AND (lockedby= '' OR lockedby= '".session_id()."' OR locktime < '".datetimestring($curlockstart)."' )" ;
                    } else{
                        $whereclause= "ID = '".$this->db->real_escape_string($this->fields['id'])."'" ;
                    }
                } else {
                    if (strtoupper($field) == 'LOCKTIME' || strtoupper($field) == 'LOCKEDBY') {
                        if ($keeplock) {
                            if (strtoupper($field) == 'LOCKTIME') {
                                $setclause .= $comma.$this->dbquote.$field.$this->dbquote."= '".lib::nowf()."'"; //reset the lockstart to now
                            } else {
                                $setclause .= $comma.$this->dbquote.$field.$this->dbquote."='".session_id()."'"; //refresh the session ID
                            }
                        } else {
                              $setclause .= $comma.$this->dbquote.$field.$this->dbquote."=''"; //clear the lock fields
                        }
                        $comma = ", ";
                    } else if (!is_null($val)) { // if null is required in the database, the value should be set to the string 'null', not null
                        $setclause .= $comma.$this->dbquote.$field.$this->dbquote." = ".((strtoupper($val)=='NULL') ? 'null' : ("'".$this->db->real_escape_string($val)."'"));
                        $comma = ", ";
                    }
                }
              }
            }
            $success =  $this->update($setclause, $whereclause, $numrows,$errormessage, $trace, $matchedrows);
            if ($this->trace|| $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." set ".$setclause." where ".$whereclause." success=".$success." numrows= ".$numrows." matchedrows = ".$matchedrows." <br>\n"; }
        } else {
          if ($this->trace|| $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." <br>\n"; }
        }
        return ($success && ($matchedrows == 1)); // even if the record lock is lost, the update will return true but $numrows = 0
     }
    public function select($fieldselection, $whereclause, $groupby,$having,$orderby,$locktype, &$results, &$numrows=0, $trace=false, $noerrorhandler=false){
        if ($this->trace|| $trace) { echo gtab(1)."Enter ".$this->tablename." : ".__METHOD__.". select ".$fieldselection." where ".$whereclause,"<br>\n"; }
        // lib::pr("=============== ".__METHOD__."=================".$this->tablename,$this->db,"===============END ".__METHOD__."=================");
        global $domainid; 
        $success =  $this->db->select (lib::capsToUnderscores($this->tablename), $fieldselection, $whereclause,$groupby,$having,$orderby,$locktype==''?0:$locktype, $results, $numrows,$trace, $noerrorhandler);
        if ($this->trace|| $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."(".$numrows." rows found))<br>\n";} 
        return $success;        
     }
    public function multiselect($as, $joins, $fieldselection, $whereclause,$groupby,$having,$orderby,$locktype, &$results, &$numrows=0, $trace=false){
        global $domainid; 
        if ($this->trace|| $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success =  $this->db->multiselect(lib::capsToUnderscores($this->tablename), $as, $joins, $fieldselection, $whereclause,$groupby,$having,$orderby,$locktype==''?0:$locktype, $results, $numrows, $trace);
        if ($this->trace|| $trace ) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."(".$numrows." rows found))<br>\n";} 
        return $success;        
     }
    public function countrecords($whereclause,&$numrows=0,$trace=false) {
        if ($this->trace|| $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        return $this->select("id", $whereclause,"","","",0,$results,$numrows,$trace);
     }
    public function query($query, &$results, &$numrows=0, $trace=false){
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success =  $this->db->dbquery($query, $query_result, $numrows,$errormessage, 1, 0,$matched,$trace,false);
        if ($success) {
            $this->db->processresults($query_result,$results,false,$trace);
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."(".$numrows." rows affected OK? = {$success})<br>\n"; }
        return $success;        
     }
    public function delete($whereclause, &$numrows=0, $trace=false, &$errormessage=""){
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $query = "DELETE FROM ".lib::capsToUnderscores($this->tablename);
        $query .= (strlen($whereclause) === 0) ? "" : (" WHERE ".$whereclause);
        if ($this->trace || $trace) { echo gtab()."Query ".$query."<br>\n"; }
        $success =  $this->db->dbquery($query, $this->result, $numrows,$errormessage, 1, $trace);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."(".$numrows." rows affected. ".$errormessage.")<br>\n"; }
        return $success;        
     }
    public function createfromfields($formfields, &$id,  $recoverid=false, $trace=false){ 
        // $fields is assumed to be an array["fieldname"=>"value"]
        if ($this->trace|| $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $this->clear();
        $this->loadfromfields($formfields);
        $success = $this->insert($recoverid,$id);
        // $id = $this->fields["id"];
        if ($this->trace|| $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."<br>"; }
        return $success;
     }
    protected function processdbresultset($dbresults,&$records){
        // transfers the data from the db results into the $records arrays.
        // the $fields array determines 
        // if $numrows = 1 the results will be in $fields and $records[1] 
        // if $numrows > 1 all the results will be in accumulated in $records array, 
        // but just the last record processed will be left in $fields
        $records = array(); 
        foreach ($dbresults as $result ) {
            $this->initfields($this->fields); // clears values from fields array, but not keys 
            foreach ($this->fields as $var=>$val) {
                if (isset($result[$var])) {
                    $this->fields[$var] = $result[$var]; 
                }
            }
            $records[] = $this->fields; // accumulate $fields in $records
        }
     }
    public function selectonID($id, &$record,&$numrows = 0, $withlock=0, $trace=false){ //loadfromdb
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."  id = ".$id."<br>\n"; }
        $this->initfields($this->fields);
        $records = array();
        $dbresults = [];
        $success = $this->select("*", 'id = "'.$this->db->real_escape_string($id).'"','','','',$withlock,$dbresults, $numrows, $trace);
        if ($success) {
            $success = $numrows == 1;
            if ($success) {
                if ($withlock) {
                    // $success = $this->lock($id, session_id());
                }
                if ($success) { //load dbresults into local fields
                    $this->processdbresultset($dbresults,$records);
                    $record = $records[0]; 
               }
            }
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." (".$numrows." rows found)<br>\n"; }
        return $success;
     }
    public function selectall(&$records, &$numrows = 0, $orderby="",$trace=false, $noerrorhandler=false) { //loadallfromdb
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success = $this->select("*", '','','',$orderby,0, $dbresults, $numrows, $trace, $noerrorhandler);
        if ($success) {
            $this->processdbresultset($dbresults,$records);
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." (".$numrows." rows found)<br>\n"; }
        return $success;
     }
    public function selectallbydomain($orderby,&$results, &$numrows=0,$alldomains=false, $trace=false)     {
        // cf SELECTALL, this returns whatever's in the db - nothing is added to $this->fields
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename." :".__METHOD__."<br>\n"; }
        $success = $this->select("*",($alldomains?"":($this->ismyfield("domain_id")?("domain_id = ".$domainid):"")),'','',$orderby,0,$results, $numrows, $trace);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." (".$numrows." rows found)<br>\n"; }
        return $success;
     }
    public function selectononefield($field,$value,&$records,&$numrows=0,$withlock=false, $trace=false,$order="")  	{ //loadfromdbfield
        if ($this->trace|| $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."  ".$field." = ".$value."<br>\n"; }
        try {
            $this->initfields($this->fields);
            $success = $this->select("*", ($field.' = "'.$this->db->real_escape_string($value).'"'),'','',$order,0, $dbresults, $numrows, $trace);
            if ($success) {
                $this->processdbresultset($dbresults,$records);
            }
            if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." (".$numrows." rows found)<br>\n"; }
            return $success;
        } catch(\Exception $e) {
            echo __METHOD__." exception ".$e->message;
        }
     }
    public function selectonmultiplefields($fielddata,&$records,&$numrows = 0, $withlock=false, $trace=false)  	{ //loadfromdbfields
         if ($this->trace|| $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $this->initfields($this->fields);
        $whereclause = '';
        if (isset($fielddata)) {
            foreach ($fielddata as $field=>$value) {
                $whereclause .= ((strlen($whereclause))?(" AND "):"").$field.' = "'.$this->db->real_escape_string($value).'"';
            }  
        }  
        $success = $this->select("*", $whereclause,'','','',0,  $dbresults, $numrows, $trace);
        if ($success) {
            if ($numrows == 1 && $withlock) {
                $success = $this->lock($id, session_id());
            }
            if ($success) {
                $this->processdbresultset($dbresults,$records);
            }
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." (".$numrows." rows found)<br>\n"; }
        return $success;
     }
    public function lock($id, $session_id) 	{
        if ($this->trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        // a database record is locked if it has a lockedbysession ID and has a current lockedtime
        // to take a lock, these must be set by this session, but only if there is no current lock by another session.
        // all subsequent updates on the locked record must test this lock as part of where clause
        global $now;
        $setclause = $this->dbquote."LOCKEDBY".$this->dbquote." = '".$session_id."', ".$this->dbquote."LOCKTIME".$this->dbquote." = '".lib::nowf()."'";
        $validlockstart = $this->getvalidlockstart($now);
        $whereclause= "ID = '".$id."' AND (LOCKEDBY= '' OR LOCKEDBY= '".$session_id."' OR LOCKTIME < '".datetimestring($validlockstart)."' )" ;
        if ($this->trace) { echo gtab()."LOCK SET ".$setclause." WHERE ".$whereclause."<br>\n"; }
        $success = $this->update ($setclause, $whereclause, $numrows,$errormessage);
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $success;
     }
    public function unlock($id, $session_id) 	{
        if ($this->trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        // a database record is locked if it has a lockedbysession ID and has a current lockedtime
        // to take a lock, these must be set by this session, but only if there is no current lock by another session.
        // all subsequent updates on the locked record must test this lock as part of where clause
        $setclause = $this->dbquote."LOCKEDBY".$this->dbquote." = '', ".$this->dbquote."LOCKTIME".$this->dbquote." = ''";
        $whereclause= "ID = '".$id."' AND LOCKEDBY= '".$session_id."'" ;
        if ($this->trace) { echo gtab()."LOCK SET ".$setclause." WHERE ".$whereclause."<br>\n"; }
        $success = $this->update ($setclause, $whereclause, $numrows, false, $matchedrows);
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $success;
     }
    public function put($fieldname, $data, $save=false,$trace=false)     { 
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__.":".$this->tablename." fieldname = ".$fieldname." data = ".$data." id = ".$this->fields["id"]."<br>\n"; }
        $this->fields[$fieldname] = $data; 
        if ($save) {
            $setclause = $this->dbquote.$fieldname.$this->dbquote." = ".(($data=="null" || is_null($data)) ? "null" : ("'".$this->db->real_escape_string($data)."'"));
            $whereclause = " id = '".$this->db->real_escape_string($this->fields["id"])."'";
            $success = $this->update($setclause, $whereclause, $numrows,$errormessage);
        } else {
            $success = true;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." /success = ".$success."<br>\n"; }
        return $success;        
     }
    public function putfields(&$id,$fieldnames,$data,$save,&$numrows,&$errormessage,$trace=false)     { 
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $setclause = ""; 
        $this->clear();
        foreach ($fieldnames as $key => $fieldname) {
            $this->fields[$fieldname] = $data[$key];
            if ($save && $id != 0) {
                $setclause .= ($setclause == "" ? "":", ");
                $setclause .= $this->dbquote.$fieldname.$this->dbquote." = ".(($data=="null" || is_null($data)) ? "null" : ("'".$this->db->real_escape_string($data[$key])."'"));
            }
        }
        if ($save) {
            if ($id==0) {
                $success = $this->insert(true,$id,$trace,$em);
            } else {
                $whereclause = " id = {$id}";
                $success = $this->update($setclause, $whereclause, $numrows,$em);
            }
            $errormessage .= $em;
        } else {
            $success = true;
        }
        if ($this->trace || $trace ) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."id=$id success=$success<br>\n"; }
        return $success;        
     }
    protected function beforeinsert(&$fnames,&$fvalues){}
    protected function beforeupdate(&$set){}
}
