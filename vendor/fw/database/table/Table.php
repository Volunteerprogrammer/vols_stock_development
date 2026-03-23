<?php
namespace fw\database\table;
use \lib\StdLib as lib;
abstract class Table
{
    private   $trace = false;
    protected $db;
    protected $result;
    protected $tablename;
    protected $tableclassname;
    protected $fields = array();  // name=>value pairs for all fields in the table
    protected $records = array();  // array of $fields
    protected $prompts = array();  //name=>GUI prompt pairs for all fields in the table
    protected $errorhandler;
    protected $databaselocktimeoutsecs = 30;
               
    abstract protected function __construct();
    abstract protected function init($db);
//    abstract protected function getcolumnnames($table, &$columns) ;
    abstract protected function insert($recoverid=true,&$id=0, $trace=false);
    abstract protected function getvalidlockstart($now);
    abstract protected function update($set_clause, $whereclause, &$numrows,&$errormessage, $trace=false, &$matchedrows=0, $keeplock=false);
    abstract protected function updateallfields(&$numrows,&$errormessage, $keeplock=false, $trace=false);
    abstract protected function select($fieldselection, $whereclause,$groupby,$having,$orderby,$locktype,  &$results, &$numrows, $trace=false);
    abstract protected function delete($whereclause, &$numrows = 0, $trace=false);
    abstract protected function createfromfields($formfields, &$id, $recoverid=false, $trace=false);
    abstract protected function selectonID($id, &$record,&$numrows = 0, $withlock=false, $trace=false);
    abstract protected function selectall(&$records,&$numrows = 0,$orderby="", $trace=false);
    abstract protected function selectononefield($field, $value, &$records,&$numrows = 0, $withlock=false, $trace=false);
    abstract protected function selectonmultiplefields($fielddata,&$records, &$numrows = 0, $withlock=false, $trace=false);
    abstract protected function lock($id, $session_id);
    abstract protected function unlock($id, $session_id);
    abstract protected function put($fieldname, $data, $save=false);

/*=========================================
    public function clear() 
    public function loadfromfields($formfields, $trace=false) 
    public function getfields() 
    public function ismyfield($fieldname) 
    public function get($fieldname) 
    public function set($fieldname,$value) 
    public function tablename() 
===========================================*/
    public function starttransaction ($flags=0,$name=null) {
        return $this->db->starttransaction($flags,$name);
     }
    public function commit ($flags=0,$name="") {
        return $this->db->commit($flags,$name);
     }
    protected function __destroy() 	{
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__."<br>"; }
     }
    protected function clearerrors() 	{
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__."<br>"; }
        unset($this->errors);
     }
    public function clear() 	{
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__."<br>"; }
        if (isset($this->fields)) $this->fields = array_fill_keys(array_keys($this->fields), ""); 
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__."<br>"; }
     }
    public function loadfromfields($formfields, $trace=false) 	{ // $formfields is assumed to be an array["fieldname"=>"value"]
        if ($this->trace || $trace) { echo "Enter ".$this->tablename.":".__METHOD__."<br>"; }
        if (isset($formfields)) {
            foreach($formfields as $field=>$val) {
                if (isset($this->fields[$field])) { // does this $formfields field exist as a field in this db class
                    $this->fields[$field] = $val;
                }
            }
        }    
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__."<BR>"; }
     }
    public function getfields() 	{
        if ($this->trace) { echo "Visit ".$this->tablename.":".__METHOD__."<br>"; }
        return $this->fields; 
     }
    public function ismyfield($fieldname) 	{
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        return isset($this->fields[$fieldname]); 
     }
    public function getfieldvalue($fieldname) 	{
        if ($this->trace) { echo "Visit ".$this->tablename.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        return isset($this->fields[$fieldname])? $this->fields[$fieldname] : '';
     }
    public function setfieldvalue($fieldname,$value) 	{
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        $this->fields[$fieldname] = $value;  // the data are escaped later in $this->update()
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__." value = ".$value."<br>"; }
        return $value; 
     }
    public function getfield($fieldname) {
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        $value = isset($this->fields[$fieldname])? $this->fields[$fieldname] : '';
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__." value = ".$value."<br>"; }
        return $value; 
     }
    public function setfield($fieldname,$value) {
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        $this->fields[$fieldname] = $value;  // the data are escaped later in $this->update()
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__." value = ".$value."<br>"; }
     }
    public function setfields($data) {
        if ($this->trace) { echo "Enter ".$this->tablename.":".__METHOD__."<br>"; }
        $this->clear();
        foreach ($data as $field=>$value) {
            if (array_key_exists($field, $this->fields)) {
                $this->fields[$field] = $value;
            }
        }
        if ($this->trace) { echo "Leave ".$this->tablename.":".__METHOD__."<br>"; }
     }
    public function tablename() {
       return $this->tablename;
     }
    protected function initfields(&$fields=array()) {
        $keys = array_keys($fields);
        foreach ($keys as $key ) { 
            $fields[$key] = "";
        }
        return $fields;
     }
    public function getclearedfields() {
        $fields = $this->fields;
        $this->initfields($fields);
        return $fields;
     }

}