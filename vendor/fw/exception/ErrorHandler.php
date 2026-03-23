<?php
namespace fw\exception;
use \lib\StdLib as lib;
class ErrorHandler 
{
    private $trace = false;
    private $login_error = array();
    private $body_error = array();
    private $db_error = array();
    private $log_output;
    private $error_type;
    private $keeplog = true;
    private $logfilename;
    private $error_emailaddress;
    private $sendemails = true;
    private $sessionid;
               
    private $headers;
    private $is_daemon;
    private $loginerrormessage ;    
    private $errormessage ;    
    private $phpsession_id;    
    private $menumanager ;    
    private $pagenum ;    
    private $pagenumparam ;    
    private $frompagenum ;    
    private $pageaccess_id ;    
    private $rights ;    
    private $session ;    
    private $config ;    

/*======================================= PUBLIC
    public function dblog($query)
    public function loginerror($message, $query)
    public function applicationerror($message, $context)
    public function sqlerror($mysqlidb=null, $message='' , $query='') 
    public function fatalerror($message) 
    public function loginerrmessage() 
    public function bodyerrmessage() 
    public function get_login_error() 
    public function get_body_error() 
    public function get_log_output() 
    public function put_login_error($data) 
    public function put_body_error($data) 
    public function put_log_output($data) 
=============================================*/
    public function __construct (protected \app\controller\manager\SupportMailManager $supportemailmgr) {
        if ( $this->trace  ) { echo "Enter ".__METHOD__."<br>"; }
     }
   private function warning_handler($errno, $errstr) { 
        // do something
    }
    public function init($config) {
        if ( $this->trace  ) { echo "Enter ".__METHOD__."<br>"; }
        $this->config = $config;
        $this->sendemails = true; // array_key_exists("ERR_EMAIL_ADDR",$config["app"]); 
        $this->keeplog =  array_key_exists("EXCEPTION_LOGFILE",$config["app"]);
        if ($this->keeplog) {
            $this->logfilename = $config["app"]["EXCEPTION_LOGFILE"];
            $maxfilesize = 65536;
            try {
                $myfilesize = filesize($this->logfilename);
                if ($myfilesize > $maxfilesize) {
                    $today = date('Ymd');
                    $archive = str_replace(".log","_".$today.".log",$this->logfilename);
                    rename($this->logfilename, $archive);
                }
            } catch (Exception $e) {
                die("Fatal error when renaming $this->logfilename to '".$today." ".$this->logfilename."'");
            } catch (Throwable $warning) {} // ignore warnings 
            $logfile = fopen($this->logfilename, "a"); //"w" - causes clearing of existing contents, "a" appends
            $sessstart = str_repeat("=",5).' START at '.lib::nowf()." ".session_id().PHP_EOL;
            fwrite($logfile,$sessstart);
            fclose($logfile);
        }
        if ( $this->trace  ) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function initphase2($session) {
        if ( $this->trace  ) { echo "Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->supportemailmgr->init($this->session);
        if ( $this->trace  ) { echo "Leave ".__METHOD__."<br>"; }
     }

    public function closelog() 	{
        if ( $this->trace) {echo "Enter ".__METHOD__."<br>";}
        if ($this->keeplog) {
            // $this->writelog(str_repeat("=",30).' END SESSION'.PHP_EOL);
        }
     }
    private function writelog($text) {
        if ( $this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // $text =(string) date('ymd His') ." ".(empty($this->sessionid)?"":"(".$this->sessionid.") ").$text."\n";
        if ($this->keeplog) {
            $logfile = fopen($this->logfilename, "a");
            fwrite($logfile, $text);
            fclose($logfile);
        }
     }
    public function dblog($query) {
        if ( $this->trace) { echo "Enter ".__METHOD__."<br>".$query."<br>"; }
        if ($this->keeplog) {
            $this->writelog($query.PHP_EOL);
            // if ($this->sendemails) {
            //    $this->supportemailmgr->sendsupportemail($message,$subject,&$responsestr,$trace=false)
            // }
        }
     }
    public function emailerror($message) {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
        if ($this->keeplog) {
            $this->writelog("Email error: ".$message.PHP_EOL);
        }
     }
    public function loginerror($message, $query) {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
        if ($this->keeplog) {
            $this->writelog("Login error: ".$message." : ".$query.PHP_EOL);
            $message .= empty($query) ? "" :("(".$query.") ");
            array_unshift($this->login_error,$message);
            if ($this->sendemails) {
               $this->supportemailmgr->sendsupportemail(("Login error: ".$message),'Login error',$responsestr,$this,false);
            }
        }
     }
    public function applicationerror($message, $context) {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
        if ($this->keeplog) {
            $this->writelog("application error : ".$message." : ".$context);
            $message .= empty($context) ? "" :("(".$context.") ");   
            array_unshift($this->body_error,$message);
            if ($this->sendemails) {
               $this->supportemailmgr->sendsupportemail(("Application error : ".$message."  Context: ". $context),'application error',$responsestr,$this,false);
            }
        }
     }
    public function applicationlog($message, $context) {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
        if ($this->keeplog) {
            $this->writelog("application log : ".$message." : ".$context);
        }
     }

    public function sqlerror($mysqlidb=null, $message='' , $query='') {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
        if ($this->keeplog) {
            $this->writelog(sprintf("%sSTART SQL error at ".lib::nowf().str_repeat("=",30)."%s",PHP_EOL,PHP_EOL));
            $this->writelog($message ." : ".$query);
            $this->writelog(sprintf("%sEND SQL error %s%s",PHP_EOL,str_repeat("<",30),PHP_EOL));
            array_unshift($this->db_error,$message);
            if ($this->sendemails) {
                $this->supportemailmgr->sendsupportemail("SQL error on query:\n{$query}\n\n{$message}",'FW database error',$responsestr,$this,false);
            }
        }
     }
    public function fatalerror($message) {
        if ( $this->trace) { echo "Enter ".__METHOD__."(".$message.") <br>"; }
            if ($this->keeplog) {
            $this->writelog("FATAL error : ".$message.PHP_EOL);
            array_unshift($this->body_error,$message);
            echo 'Fatal Error '.$message;
            if ($this->sendemails) {
               $this->supportemailmgr->sendsupportemail( $message,'FW fatal error',$responsestr,$this,false);
            }
            die("Sorry. There has been an unexpected and unrecoverable error. Support has been notified.");
        }
     }   
    public function loginerrormessage() {
        if ( $this->trace) { echo "Enter ".__METHOD__."(", var_dump($this->login_error) , ") <br>"; }
        if (count($this->login_error) ) {
            return $this->login_error[0];
        } else {
            return "";       
        }
     }
    public function bodyerrormessage() {
        if ( $this->trace) { echo "Enter ".__METHOD__."(", var_dump($this->body_error) , ") <br>"; }
        return $this->body_error[0];
     }
    public function dberrormessage() {
        if ( $this->trace) { echo "Enter ".__METHOD__."(", var_dump($this->body_error) , ") <br>"; }
        return $this->db_error[0];
     }
    public function get_login_errors(){
        return $this->login_error;
     }
    public function get_body_errors(){
        return $this->body_error;
     }
    public function get_db_errors(){
        return $this->db_error;
     }
    public function get_log_output(){
        return $this->log_output;
     }
    public function put_login_error($data){
        array_unshift($this->login_error,$data);
        $this->login_error[] = $data;
     }
	public function put_body_error($data) {
        array_unshift($this->body_error,$data);
     }
    public function put_db_error($data) {
        array_unshift($this->db_error,$data);
     }
    public function put_log_output($data) {
        $this->log_output = $data;
     }
    public function put_sessionid($sessionid) {
        $this->sessionid = $sessionid;
     }

}