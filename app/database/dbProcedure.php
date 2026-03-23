<?php
namespace cypo\database\database;

class dbProcedure  
{
    private   $trace= false;   
    private $session;   
    public function __construct($session)
    {
         $this->session = $session;
    }

    public function executefunction($db, $function,$parameters=[],&$result, &$numrows=0, $trace=false) 
	{
        if ($this->trace || $trace) { echo "Enter ".__METHOD__,":",$function."<br>\n"; }
        $query = '';
        foreach ($parameters as $param) $query .= (empty($query)?" ":", ").$param;
        $query = "SELECT ".$function.'('.$query.')';
        $success =  $db->executefunction($query, $results, $numrows, $trace);
        $result = array_values($results[0])[0];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__,":",$function."(".$numrows." rows returned)<br>\n"; }
        return $success;        
    }
    public function executeprocedure($db, $procedure,$parameters=[],&$resultset,&$outparams, &$numrows=0, $trace=false,&$sqlresultcode='',&$sqlresultmsg='') 
	{ //OUT parameters should be represented in $parameters as "OUTparamname"  e.g. "OUTinvoicenum"
          //OUT parameters will come back in $results as e.g. ["invoicenum"=>1004] 
          //INOUT parameters should be represented in $parameters as "INOUTinitialvalue"  e.g. "INOUT5.1". 
          //INOUT parameters will come back in $results as e.g. [io0=>6.3,io3=>15] when 0,3 are the respective keys in $parameters  
        if ($this->trace || $trace) { echo "Enter ".__METHOD__.$procedure."<br>\n"; }
        $parameters[] = "OUTsqlresultcode";
        $parameters[] = "OUTsqlresultmsg";
        $success = $db->executeprocedure($procedure,$parameters,$resultset,$outparams, $numrows,$trace);
        // deal with the locally inserted SQL error parameters - read them into caller's arguments, publish to the error handler if necessary, remove the params from $outparams 
//        if ($success) { 
            if (!empty($outparams[0]['sqlresultcode'])) {
                $sqlresultcode = $outparams[0]['sqlresultcode']; 
                $sqlresultmsg = $outparams[0]['sqlresultmsg']; 
                $errorhandler = $this->session->geterrorhandler();
                $errorhandler->sqlerror(NULL,"Query Failed:(".$sqlresultcode.") ".$sqlresultmsg, " \n(".$procedure.") ")     ;
                $success = false; 
            }  
//        } 
        unset($outparams[0]['sqlresultcode'],$outparams[0]['sqlresultmsg']);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__.$procedure.': '.$success." (".$numrows." rows returned)<br>\n"; }
        return $success;      

    }
}