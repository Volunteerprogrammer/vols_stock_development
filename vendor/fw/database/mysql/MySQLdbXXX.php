<?php
namespace fw\database\mysql;

/*é*/

class MySQLdb extends \fw\database\DataBase
{
    private $trace = false;
    private $mysqli = null;
    private $res = null;
    private $insert_id = null;
    private $affected_rows = null;
    private $errorhandler = null;
    private $indent = str_repeat("&nbsp;",4);
    private $databasename;
    private $host;
    private $username;
    private $password;
    public function init($errorhandler){
        if ($this->trace) {echo "Enter ".__METHOD__."\n<br>"; }
        $this->errorhandler = $errorhandler;
    }
    public function __destruct()    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        if (isset( $this->mysqli)) {
            $this->mysqli->close() ; //$link
        } 
    }
    public function connect($collection_code="",$use_collection_databases=false){
        if ($this->trace ) {echo "Enter ".__METHOD__."(Collection = $collection_code )\n<br>"; }
        $config = parse_ini_file(CPC_INIFILE);// "catopac.ini.php"
        $this->host = $config['DBHOST'];
        $this->databasename = $config['DBNAME'].($use_collection_databases?"_".$collection_code:""); //."_".$collection_code
        $this->username = $config['DBUSERNAME'];
        $this->password = $config['DBPASSWORD'];
        try {
            $this->openDB($this->host, $this->username, $this->password,$this->databasename);
        } catch (Throwable $e) {
            return  "!!".$e->getMessage();
        }
        if ($this->trace ) {echo "Leave ".__METHOD__."($this->databasename)\n<br>"; }
    }
    public function dbname() {
       $dbn = $this->databasename; 
       //echo "Getting ".__METHOD__." : dbname= $dbn <br>\n";
       return $dbn;
    }
    public function openDB($host, $user, $password, $dbname){
        if ($this->trace ) { echo "Enter ".__METHOD__."/".$host."/".$user."/".$password."/".$dbname."<br>"; }
        $this->mysqli = new \mysqli($host, $user, $password, $dbname) ;
        if ($this->mysqli->connect_errno) {
          $this->errorhandler->fatalerror("Connect failed: ", $this->mysqli->connect_error,$host, $user, $password, $dbname) ;
        } else {
            $this->mysqli->set_charset("utf8mb4");
            $this->mysqli->query("SET NAMES 'utf8mb4'");
        }
    }
    public function starttransaction() {
        return $this->mysqli->begin_transaction();
    }
    public function commit() {
       return $this->mysqli->commit();
    }
    public function rollback() {
       return $this->mysqli->rollback();
    }
    private function getmatches() {
        $info = $this->mysqli->info;
        if (isset($info)) {
            if (!(($pos = mb_strpos($info,"Rows matched")) === false)) {
                $info = trim(mb_substr($info,$pos + 13));
                $info = mb_substr($info,0,mb_strpos($info," "));
                return $info;
            }
        }
        return 0;
    }
    public function buildresultsarray($query_result,&$results,$trace = false) {
      if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
		if (is_object($query_result)) {
			if ($query_result->num_rows) {
				$results = array() ;
				while ($row = $query_result->fetch_array(MYSQLI_ASSOC)) {
					$results[] = $row;
				}
				$query_result->free();
			} else {
				$results = [];
			}
		} else {
			$results = [];
		}
		if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
	}
    public function settimezone($timezone=''){
       global  $siteglobals;
       $q = "SET time_zone = '".(empty($timezone)?$siteglobals["MYSQLDEFAULTTIMEZONE"]:$timezone)."'";
       return $success = $this->dbquery($q,$result,$numrows,1);
    }
    public function executefunction($function,$parameters=[],&$results, &$numrows=0, $trace=false,$log=0,$logquery=0,$logresult=0){
        if ($this->trace || $trace ) { echo "Enter : ",__METHOD__.":".$function,"<br>\n\n"; }
        $query = '';
        foreach ($parameters as $param) { $query .= (empty($query)?" ":", ").'"'.$param.'"'; }
        $query = "SELECT ".$function.'('.$query.')';
        if ($logquery)  {
            $this->errorhandler->dblog($query);
        }
        $success =  $this->dbquery($query, $query_result, $numrows, 0,$log,$mr,$trace);
        if ($success) {
            //$this->printHTMLresults($query_result, "", 1, 1);
            $this->buildresultsarray($query_result,$results);
            if ($logresult)  {
                $this->errorhandler->dblog($procedure." RESULT>>\n ".print_r($results,1));
            }
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
    public function executeprocedure($procedure,$parameters=[],&$resultset,&$outparams,&$numrows=0, $trace=false,$logquery=0,$logresult=0) {
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
                $success = $this->dbquery($q, $select_result, $numrows, 2,false,$matchedrows,$trace) ;
                $query .= (empty($query)?" @":", @").mb_substr($param,3);
            } else if (mb_substr($param,0,5)=="INOUT") {
                // create a variable in the SQL environment as "ion" where n=param's key, and initialise
                $q = "SET @io".$key." = '".mysqli_real_escape_string(mb_substr($param,5))."'";
                $success = $this->dbquery($q, $select_result, $numrows, 2, false,$matchedrows,$trace) ;
                $query .= (empty($query)?" ":", ")."@io".$key;
            } else {
                $query .= (empty($query)?" ":", ")."'".$param."'";
            }
        }
        if ($success) {
            $query = "CALL ". $procedure."(".$query.")";
            if ($this->trace || $trace) { echo $query."<br>\n"; return 1; }
            $success =  $this->dbquery($query, $select_result, $numrows, 2, $logquery,$matchedrows,$trace);
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
                    $success = $this->dbquery($query, $select_result, $numrows, 0, false,$matchedrows,$trace) ;
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
    public function dbquery($query, &$result, &$numrows, $querytype, $log=0, &$matchedrows=0, $trace=false,$html=0) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."(".$querytype." : ".$query.") <br>\n"; }
        $result = $this->mysqli->query($query) ;
        if ($log) $this->errorhandler->dblog($query) ;
        if ( $this->trace ) { echo $this->indent."Info: ".$this->mysqli->info."<br>\n"; }
        $matchedrows = $this->getmatches() ;
        $numrows = $this->mysqli->affected_rows;
        if ($result == false) {
            $error_num=$this->mysqli->errno;
            $error=$this->mysqli->error;
            $this->errorhandler->sqlerror($this->mysqli,"Query Failed:(".$error_num.") ".$error, " \n(".$query.") ")     ;
            if ( $this->trace) { echo $this->indent, "Query Failed:(".$error_num.") ".$error, " <br>&nbsp; &nbsp; (".$query.") <br>"; }
            $success = false;
        } else {
            if ($querytype == 0) { //SELECT, SHOW, DESCRIBE or EXPLAIN -these return a table of results
                $numrows = $result->num_rows;
                if ( $this->trace) {echo $this->indent, "Number of rows in result = ".$numrows."<br>\n"; }
            } else if ($querytype == 1) { //CREATE, INSERT, UPDATE, DELETE, ETC
                $this->insert_id = $this->mysqli->insert_id;
                if ( $this->trace) {echo $this->indent, "Number of rows affected = ".$numrows."<br>\n"; }
            } else  {// SET, etc
                if ($this->trace  || $trace) {echo $this->indent."Command completed<br>\n".$query."<br>\n============<br>\n"; }
            }
            $success = true;
        }
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__."<br>\n"; }
        return $success;
    }
    public function get_insert_id(){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $this->mysqli->insert_id;
    }
    public function freeresults($results){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->res->close() ;
    }
    public function real_escape_string($string){
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $this->mysqli->real_escape_string($string) ;
    }
    public function printHTMLresults($query_result, $tableclass, $colnames, $rownumbers){
        if (!is_null($query_result)) {
            $nl = "\n";
            if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
            $rn = isset($rownumbers) && $rownumbers == true;
            echo '<table'.($tableclass == ""?"":(' class="'.$tableclass.'"')).'>'.$nl;
           //column headings
            if (isset($colnames) && $colnames == true) {
                $line = $query_result->fetch_array(MYSQLI_ASSOC) ;
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
            while ($line = $query_result->fetch_array(MYSQLI_ASSOC) ){
                echo "\t<tr>".$nl;
                $i++;
                if ($rn == true) {
                    echo "\t\t<td>$i</td>".$nl;
                }
                foreach($line as $col_value) {
                    echo "\t\t<td>$col_value</td>".$nl;
                }
                echo "\t</tr>".$nl;
            } ;
            //close
            echo "</table>$nl";
        }
    }
}
