<?php
namespace database;

//THIS ONE IS CURRENTLY USED
class MySqlDB extends \fw\database\DataBase
{
    private $trace = false;   
    private $mysqli = null;
    private $res = null;
    private $insert_id = null;
    private $affected_rows = null;   
    private $errorhandler = null;
    private $indent ;
    private $databasename;
    private $host;
    private $username;
    private $password;
    private $results;
    private $outparams; 

    public function __construct(){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function __destruct()    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        if (isset( $this->mysqli)) {
            $this->mysqli->close() ; //$link
        } 
     }
    public function init($errorhandler){
        if ($this->trace) {echo "Enter ".__METHOD__."<br>"; }
        $this->errorhandler = $errorhandler;
     }
    public function connect($host, $user, $password, $dbname) {
        if ($this->trace) {echo "Enter ".__METHOD__.$host."/".$user."/".$password."/".$dbname."<br>"; }
        try {
            $this->mysqli = new \mysqli($host, $user, $password, $dbname) ;
            \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            if ($this->mysqli->connect_errno) {
                throw new Exception("Connect failed: ".$this->mysqli->connect_error);
            } else {
                $this->mysqli->set_charset("utf8mb4");
                $this->mysqli->query("SET NAMES 'utf8mb4'");
                if (!$this->mysqli->autocommit(true) ) { 
                    echo $this->indent, $this->mysqli->error."<br>\n";
                }
            }
        } catch (mysqli_sql_exception $e) {
            die("error initialising database driver: ".$e->__toString());
        }
     }    
    public function resetconnection($host, $user, $password, $dbname)    {
        try {
             $this->mysqli->close() ;
             $this->connect($host, $user, $password, $dbname);
        } catch (mysqli_sql_exception $e) {
            die("error in ".__METHOD__.": ".$e->__toString());
        }

     }
    public function starttransaction($flags=0,$name=null) {
        try {
            $this->mysqli->autocommit(FALSE); 
        } catch (mysqli_sql_exception $e) {
            die("error in ".__METHOD__.": ".$e->__toString());
        }
       // $thisname = isset($name)?$name:" ";
       return $this->mysqli->begin_transaction($flags,$name);
     }
    public function commit($flags=0,$name="")    {
        try {
            $this->mysqli->commit($flags,$name);
        } catch (mysqli_sql_exception $e) {
            die("error in ".__METHOD__.": ".$e->__toString());
        }
       return $this->mysqli->autocommit(true); 
     }
    public function rollback()    {
        try {
            $success = $this->mysqli->rollback(); 
        } catch (mysqli_sql_exception $e) {
            die("error in ".__METHOD__.": ".$e->__toString());
        }
        return $success;
     }
    private function getmatches()    {
        $info = $this->mysqli->info;
        if (isset($info)) {
            if (($pos = strpos($info,"Rows matched")) !== false) {
                $info = trim(substr($info,$pos + 13));
                $info = substr($info,0,strpos($info," "));
                return $info;
            }
        }
        return 0;
     }
    protected function clearstoredresults($query_result, $trace=false)     {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        try {
            $query_result->free();  
            while ($this->mysqli->more_results()) {
                if ($this->mysqli->next_result())  {
                    if($results = $this->mysqli->store_result()){
                        $results->free();
                    }
                }
            }
        } catch (mysqli_sql_exception $e) {
            die("error in ".__METHOD__.": ".$e->__toString());
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function buildresultsarray($query_result,&$results,$trace = false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        if (is_object($query_result)) {
            if ($query_result->num_rows) {
                $results = array() ;
                while ($row = $query_result->fetch_array(MYSQLI_ASSOC)) {
                    $results[] = $row;
                }
            } else { 
                $results = [];
            }
            $this->clearstoredresults($query_result,$trace); 
        } else { 
            $results = [];
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function settimezone($timezone='')    {
       global  $siteglobals;
       return $success = $this->dbquery("SET time_zone = '".(empty($timezone)?$siteglobals["MYSQLDEFAULTTIMEZONE"]:$timezone)."'",$result,$numrows,$errormessage,1);
     }
    public function processresults($query_result,&$results,$logresult){
        $this->buildresultsarray($query_result,$results);
        if ($logresult)  {
            $this->errorhandler->dblog($procedure." RESULT>>\n ".print_r($results,1));
        }
     }
    public function executefunction($function,$parameters,&$results, &$numrows=0, $trace=false,$log=0,$logquery=0,$logresult=0){
        if ($this->trace || $trace ) { echo "Enter : ",__METHOD__.":".$function,"<br>\n\n"; }
        $query = '';
        foreach ($parameters as $param) { $query .= (empty($query)?" ":", ").'"'.$param.'"'; }
        $query = "SELECT ".$function.'('.$query.')';
        if ($logquery)  {
            $this->errorhandler->dblog($query);
        }
        $success =  $this->dbquery($query, $query_result, $numrows,$errormessage, 0,$log,$mr,$trace);
        if ($success) {
            $success = $this->processresults($query_result,$results,$logresult);
            // $this->buildresultsarray($query_result,$results);
            // if ($logresult)  {
            //     $this->errorhandler->dblog($procedure." RESULT>>\n ".print_r($results,1));
            // }
        } else {
            if ($logresult)  {
                $this->errorhandler->dblog($procedure." FAILED\n ".print_r($results,1));
            }
            $results[] = [];
        }
        $results = array_values($results[0]);
        if ($this->trace || $trace) { echo "Leave : ".__METHOD__.":".$function."(".$numrows." rows returned)<br>\n\n";var_dump($results); }
        return $success;
     }
    public function executeprocedure($procedure,$parameters,&$resultset,&$outparams,&$numrows=0, $trace=false,$logquery=0,$logresult=0) {
        /* OUT parameters should be represented in $parameters as "OUTparamname"  e.g. "OUTinvoicenum"
        //OUT parameters will come back in $outparams as e.g. ["invoicenum"=>1004]
        //INOUT parameters should be represented in $parameters as "INOUTinitialvalue"  e.g. "INOUT5.1".
        //INOUT parameters will come back in $outparams as e.g. [io0=>6.3,io3=>15] when 0,3 are the respective keys in $parameters
        //this is intended to work with single value out parameters, not results sets
        // resultset is a 2D array with `item` and `mda` columns and one item per row */
        if ($this->trace || $trace) { echo "!!Enter ".__METHOD__.":".$procedure."<br>\n"; }
        global $ob;
        $success = true;
        $query = '';
        foreach ($parameters as $key=>$param) {
            if (mb_substr($param,0,3)=="OUT") {
                // create a variable in the SQL environment using the $param name and initialise as empty
                $q = "SET @".mb_substr($param,3)." = ''";
                $success = $this->dbquery($q, $select_result, $numrows,$errormessage, 2,false,$matchedrows,$trace) ;
                $query .= (empty($query)?" @":", @").mb_substr($param,3);
            } else if (mb_substr($param,0,5)=="INOUT") {
                // create a variable in the SQL environment as "ion" where n=param's key, and initialise
                $q = "SET @io".$key." = '".mysqli_real_escape_string(mb_substr($param,5))."'";
                $success = $this->dbquery($q, $select_result, $numrows,$errormessage, 2, false,$matchedrows,$trace) ;
                $query .= (empty($query)?" ":", ")."@io".$key;
            } else {
                $query .= (empty($query)?" ":", ")."'".$param."'";
            }
        }
        if ($success) {
            $query = "CALL ". $procedure."(".$query.")";
            if ($this->trace || $trace) { echo $query."<br>\n"; return 1; }
            $success =  $this->dbquery($query, $select_result, $numrows,$errormessage, 2, $logquery,$matchedrows,$trace);
            if ($success) { //Retrieve any OUT or INOUT variables from the SQL environment
                $this->buildresultsarray($select_result,$resultset);
                if ($logresult)  {
                  if (array_key_exists(0,$resultset)) {
                    $this->errorhandler->dblog($procedure." RESULT>>\n ".print_r($resultset[0],1));
                  } else {
                    $this->errorhandler->dblog($procedure." RESULT>> No items match the search.");
                  }
                }
                $this->mysqli->next_result();
                $query = "";
                foreach ($parameters as $key=>$param) {
                    if (mb_substr($param,0,3)=="OUT") {
                        $query .= (empty($query)?"@":", @").mb_substr($param,3)." as ".mb_substr($param,3);
                    } else if (mb_substr($param,0,5)=="INOUT") {
                        $query .= (empty($query)?"":", ")."@io".$key." as io".$key;  // the calling proc needs to manage the semantics of such results
                    }
                }
                if (mb_strlen($query))  {
                    $query = "SELECT ".$query;
                    $success = $this->dbquery($query, $select_result, $numrows,$errormessage, 0, false,$matchedrows,$trace) ;
                    if ($success) {
                        $this->buildresultsarray($select_result,$outparams);
                        if ($logresult)  { $this->errorhandler->dblog($procedure." OUTPARAMETERS>>".print_r($outparams[0],1)); }
                    }
                } else {
                    $outparams = [];
                }
            }
        }
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__.":".$procedure."<br>\n"; }
        return $success;
     }
    public function dbquery($query, &$result, &$numrows,&$errormessage, $querytype, $log=0, &$matchedrows=0, $trace=false, $noerrorhandler=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."(".$querytype." : ".$query.") <br>\n"; }
        try {
            if ($this->mysqli->begin_transaction()) {
                $result = $this->mysqli->query($query) ;
                if ($log && !$noerrorhandler) $this->errorhandler->dblog($query) ;
                if ( $this->trace ) {echo $this->indent."Info: ".$this->mysqli->info."<br>\n"; }
                $matchedrows = $this->getmatches() ;
                $numrows = $this->mysqli->affected_rows;
                if ($result == false) {
                    $error_num=$this->mysqli->errno;
                    $error=$this->mysqli->error;
                    $errormessage = __METHOD__." Query Failed: ($error_num) $error,  \n($query)";
                    if (!$noerrorhandler) {
                        $this->errorhandler->sqlerror($this->mysqli,$errormessage)     ;
                    }   
                    if ( $this->trace) { echo $this->indent, $errormessage; }
                    $success = false;
                    $this->mysqli->rollback();
                } else {
                    if ($querytype == 0) { //SELECT, SHOW, DESCRIBE or EXPLAIN -these return a table of results
                        $numrows = $result->num_rows;
                        if ( $this->trace || $trace) {echo $this->indent, "Number of rows in result = ".$numrows."<br>\n"; }
                    } else if ($querytype == 1) { //CREATE, INSERT, UPDATE, DELETE, ETC
                        $this->insert_id = $this->mysqli->insert_id;
                        if (substr($query,0,6)=="INSERT") {
                            $result = $this->insert_id;
                        }
                        if ( $this->trace) {echo $this->indent, "Number of rows affected = ".$numrows."<br>\n"; }
                    } else  {// SET, etc
                        if ($this->trace  || $trace) {echo $this->indent."Command completed<br>\n".$query."<br>\n============<br>\n"; }
                    }
                    $success = true;
                    $this->mysqli->commit();
        // \lib\StdLib::e($success,$query);        
                }
            } else {
                if (!$noerrorhandler) { 
                    $this->errorhandler->sqlerror($this->mysqli,"Failed starting transaction.");     
                }
                $success = false;
             }
        } catch(\Exception|\mysqli_sql_exception $e) {
            $errormessage = "Query Failed:(".$e->getCode().")<br><br>".$e->getMessage()." \n";
            if (!$noerrorhandler) {
                $emailmessage = $e->getCode()." ".$e->getMessage()."\n\n".$e->__toString();
                $this->errorhandler->sqlerror($this->mysqli,"$emailmessage\n\n",$query);
            }
           $success = false;        
        }
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__." success = $success<br>\n"; }
        return $success;
     }
    public function select($tablename, $fields, $where_clause,$groupby,$having,$orderby,$locktype, &$results, &$numrows, $trace=false, $noerrorhandler=false) {
        // $locktype - 0 = none, 1 = sharemode, 2 = for update
        if ($this->trace || $trace) { echo "Enter : ".__METHOD__."<br>"; }
        $query  = "SELECT ";
        $query .= (strlen($fields) === 0) ? "*" : $fields;
        $query .= " from `".$tablename."` ";
        $query .= (strlen($where_clause) === 0) ? "" : " WHERE ".$where_clause;
        $query .= strlen($groupby)?" GROUP BY ".$groupby:"";    
        $query .= strlen($having)?" HAVING ".$having:"";    
        $query .= strlen($orderby)?" ORDER BY ".$orderby:"";    
        // $locktype - 0 = none, 1 = sharemode, 2 = for update
        $query .= $locktype?($locktype==1?" LOCK IN SHARE MODE":" FOR UPDATE"):"";    
        // \lib\StdLib::pr($query);
        $success = $this->dbquery($query, $select_result, $numrows,$errormessage, 0, false, $matchedrows, $trace, $noerrorhandler) ;
        if ($success) {
            $this->buildresultsarray($select_result,$results,$trace);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;       
     }
    public function multiselect($tablename, $as, $joins, $fields, $where_clause,$groupby,$having,$orderby,$locktype,  &$results, &$numrows) {  
        // $joins is an array of join definitions: each row is an array (tablename, 'AS' name, 'ON clause', [JOIN TYPE]).  Default join type is INNER
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $query = "SELECT ";
        $query .=((strlen($fields) === 0) ? "*" : $fields)."\n";
        $query .= " FROM `".$tablename."` ".(!$as==""?("AS ".$as):"")."\n";
        foreach($joins as $table) { //$table assumed to be a 3-element array containing the JOIN tablename, an optional AS name, and an optional ON clause
                                    // optional fourth element is an alternative JOINTYPE to the default ("INNER")
            if (!isset($table[3])) {
                $query .= " INNER JOIN ".$table[0].(!$table[1]==''?(" AS ".$table[1]):'').(!($table[2]=="")?(" ON ".$table[2]):'')."\n";
            } else {
                $query .= " ".$table[3]." JOIN ".$table[0].(!$table[1]==''?(" AS ".$table[1]):'').(!($table[2]=="")?(" ON ".$table[2]):'')."\n";
            }
        } 
        $query .= (!(strlen($where_clause) === 0)?(" WHERE ".$where_clause):'')."\n";
        $query .= strlen($groupby)?" GROUP BY ".$groupby:"";    
        $query .= strlen($having)?" HAVING ".$having:"";    
        $query .= strlen($orderby)?" ORDER BY ".$orderby:"";    
        $query .= $locktype?$locktype==1?"LOCK IN SHARE MODE":"FOR UPDATE":"";    
        $success = $this->dbquery($query, $select_result, $numrows,$errormessage,$errormessage, 0) ;
        if ($success) {
            $this->buildresultsarray($select_result,$results);
        }
        if ($this->trace) { echo "Leave ".__METHOD__."<br>\n"; }
        return $success;       
     }
    public function get_insert_id()    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $this->mysqli->insert_id;
     }
    public function freeresults($results)    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->res->close() ;
     }
    public function real_escape_string($string)    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $this->mysqli->real_escape_string($string) ;
     }
    public function printHTMLresults($result, $tableclass, $colnames, $rownumbers)    {
        $nl = "\n";
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $rn = $rownumbers??false;
        echo '<table class="', $tableclass, '">'.$nl;
        //column headings
        if ($colnames??false) {
            $line = mysqli_fetch_array($result, MYSQL_ASSOC) ;
            echo "\t<tr>$nl";
            if ($rn == true) {
                echo "\t\t<th>#</th>".$nl;
            }
            foreach(array_keys($line) as $col_value) {
                echo "\t\t<th>$col_value</th>".$nl;
            }
           
            echo "\t</tr>".$nl;
        }
        //rows
        $i=0;
        do {
            echo "\t<tr>".$nl;
            $i++;
            if ($rn == true) {
                echo "\t\t<th>$i</th>".$nl;
            }
            foreach($line as $col_value) {
                echo "\t\t<td>$col_value</td>".$nl;
            }
            echo "\t</tr>".$nl;
        } while ($line = mysqli_fetch_array($result, MYSQL_ASSOC) ) ;
        //close
        echo "</table>$nl";
     }
}