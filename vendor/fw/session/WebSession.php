<?php
namespace fw\session;

class WebSession 
{
    protected $trace= false;
    protected $allowedpages;
    protected $config;
    protected $db;
    protected $domainname = "";
    protected $errorhandler;      
    protected $errormessage ;    
    protected $fields = [];
    protected $frompagenum ;    
    protected $greeting = "";
    protected $headers;
    protected $homepage;
    protected $is_daemon;
    protected $isadmin = "unset";
    protected $isloginsubmit;
    protected $isperson = 0;
    protected $login_expired = false;
    protected $loginerrormessage = "";
    protected $menumanager ;    
    protected $pageaccess_id ;    
    protected $pagenum ;    
    protected $pagenumparam ;    
    protected $pagemanager ;    
    protected $phpsession_id;    
    protected $position = "";
    protected $previous_user = "";
    protected $requestdata;
    protected $rights ;    
    protected $rightsmanager;
    protected $user_id;
    protected $userfields;  
    protected $usermanager;
    protected $user_menu_number;
    protected $p = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
    protected $r = ["\t[","ray","\t)","="];
/*======================================================================*/
    public    function __construct ( protected \apptable\PhpSessionTable $phpsessiontable,
                                     protected \apptable\PageAccessTable $pageaccess,
                                     protected \apptable\UserTable $table,
                                 ) {
        session_start();    // this is the php session
        $this->phpsession_id = session_id();     // we will use this php-generated id as the id for our session also
     }
    public    function init($errorhandler,$db,$managercollection,&$requestdata,&$norights,&$config,$is_daemon)     {
        if ($this->trace)  { echo gtab(1)."Enter ".__METHOD__."==============================================<br>"; }
        try {
            $this->errorhandler = $errorhandler;
            $this->db = $db;
            $this->getmanagers($managercollection);
            $this->config = $config;
            $this->homepage = -1;
            $this->requestdata = $requestdata;
            $this->pagenum = (int) trim ((!empty($this->requestdata['p'])) ? $this->requestdata['p'] : 0 );
            $this->pagenumparam = (int) trim ((!empty($this->requestdata['pagedata'])) ? $this->requestdata['pagedata'] : "" );
            $this->frompagenum = (int) trim ((empty($this->requestdata['pp'])) ? 0 : $this->requestdata['pp'] );
            $this->isloginsubmit = isset($this->requestdata['formname']) && in_array($this->requestdata['formname'],["loginform","pwresetgetemail","pwresetgetcode","pwresetgetpw"]) && !isset($this->requestdata['recordselector']) && !isset($this->requestdata['menuid']);
            $this->rights = [];
            $notes  = "";
            if ($is_daemon) {
                return true;
            }  
            $this->phpsessiontable->init($this->db);
            $this->pageaccess->init($this->db);
            $this->errorhandler->initphase2($this); 
            if (!$this->isloginsubmit && $this->menumanager->islogout($this->pagenum) ) {
                $notes .= "Logout request\n";
                $this->restartphpsession();
                unset($_SESSION['count']);
                $success = true;
            } else {
                $this->incsessioncount();
                try {
                    $success = $this->loadsession($notes,$this->pagenum,$this->loginerrormessage,$this->frompagenum,$norights,$this->trace);  
                } catch (\Exception $e) {
                    die("Unable to initialise the session");
                }    
                $this->requestdata['p'] = $this->pagenum;
                $this->errorhandler->put_sessionid($this->phpsessiontable->getfield("id")); 
            }
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        } catch (\Exception $e) {
            throw $e;
        }
        return $success;    
        // \lib\StdLib::v("init b",$notes,$this->pagenum);                
     }
    private   function getmanagers ($managercollection) {
        $this->usermanager = $managercollection->usermanager();
        $this->pagemanager = $managercollection->pagemanager();
        $this->menumanager = $managercollection->menumanager();
        $this->menumanager->init($this); 
     }
    protected function loadsession($notes,&$pagenum,&$errormessage,$frompagenum,&$norights,$trace=false)     {
        if ($this->trace   || $trace  ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $follow  = 0;
        $rows = 999;
        $success = $this->phpsessiontable->selectononefield('phpsession',$this->phpsession_id,$phpsessionrecords,$rows);
        if ($follow){ \lib\StdLib::e("loadsession==> ",$pagenum); }     
        $p = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
        $r = ["\t[","ray","\t)","="];
        if ($follow){ \lib\StdLib::pr("loadsession: success=$success, ",$this->phpsession_id,$phpsessionrecords,$rows,$this->isloginsubmit); }     
        if ($success && $rows == 1 && !$this->isloginsubmit ) { // the session already exists
            $this->user_id = $this->phpsessiontable->getfield("user_id");
            if ($follow){ \lib\StdLib::e("SESSION USER ID => ",$this->user_id," success= $success");} 
        }
        if ($success ) {
            if ($this->isloginsubmit){
                // do nothing here - pass on to processing the login
                $notes .= " This is a login request.\n";
            } else {
                //check if the login has timed out 
                if ($rows > 0 && $this->user_id) {
                    $this->login_expired = $this->issessiontimedout($trace);
                    if ($this->login_expired) {
                        if ($follow){ \lib\StdLib::e("session is timed out ",$this->user_id,$pagenum," success= $success");} 
                        $notes .= "Session timeout heading for page ".$pagenum.".\n";
                        $errormessage = "Your session has timed out. Please login again.";
                    }
                }
                if ($this->login_expired || $rows==0) {
                    if ($follow){  \lib\StdLib::e("need restart  ",$rows,$this->user_id,$pagenum,$this->isloginsubmit," success= $success");} 
                    //need to start a new session for a new login
                    $this->restartphpsession();
                    $success = $this->insertnewsessionrecord(false);
                    $pagenum = 1; 
                } else {
                    if ($follow){ \lib\StdLib::e("no timeout  ",$rows,$this->user_id,$pagenum," success= $success");                } 
                    if ($rows==1) {
                        $success = $this->loaduser($this->user_id,false,$errormessage,$trace);
                        //check that the user has the right to visit the target page 
                        if ($pagenum > 0 ) {
                            if (!($this->isadmin || in_array($pagenum."||VIEW",$this->rights) || $this->pagemanager->pageisunrestricted($pagenum))) {
                                $pagenum = $frompagenum;
                                $norights = true;
                                $notes .= "User {$this->user_id} does not have rights for page ".$pagenum.".\n";
                                $errormessage = "You do not have rights for that page.";
                            }
                        }
                        if ($follow){\lib\StdLib::pr($pagenum."||VIEW",$this->rights);}
                        if ($follow){\lib\StdLib::e("access OK ",$pagenum,$this->user_id," success= $success");}
                        $now = \lib\StdLib::nowf();
                        $success = $this->phpsessiontable->put("last_access", $now, true,$trace);
                        if ($follow){  \lib\StdLib::e("put(last_access) ",$now," success= $success");} 
                    } else { //Multiple records found for Session id - start a new session
                        $this->errorhandler->applicationerror ("Multiple({$rows}) records found for Session id (new session started): ",$this->phpsession_id);
                        $notes .= "Multiple({$rows}) session records (phpsession_id = ".$this->phpsession_id.") heading for page ".$pagenum.". New session started.\n";
                        $this->restartphpsession();
                        $success = $this->insertnewsessionrecord();
                        $pagenum=0;    // send the user back to the home page
                        $errormessage = "Sorry, there is a problem in the data. We can't continue.";
                    }
                }
                if ($follow){ \lib\StdLib::e("finally  ",$this->phpsession_id,$pagenum,$notes,$errormessage,$this->user_id," success= $success");                } 
                if ($success) {
                    $this->pageaccess->clear();
                    $this->pageaccess->setfieldvalue("session_id",$this->phpsessiontable->getfield("id"));
                    $this->pageaccess->setfieldvalue("page_num",$pagenum);
                    $this->pageaccess->setfieldvalue("notes",$notes);
                    $this->pageaccess_id = $this->pageaccess->insert(false);
                }
            }
        }   
        if ($this->trace   || $trace  ) { echo gtab(-1)."Leave ".__METHOD__."  User id ".$this->user_id."<br>\n"; }
        if ($follow){  \lib\StdLib::e("e >> ",$pagenum," success= $success"); } 
        return $success;
     }
    private   function incsessioncount() {
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        if (!isset($_SESSION['count'])) {        
            $_SESSION['count'] = 1;
        } else {
            $_SESSION['count']++;
        }
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    protected function loaduser($user_id,$fromlogin=false,&$errormessage='',$trace=false)     {
        if ($this->trace  || $trace  ) { echo gtab(1)."Enter ".__METHOD__."(user = ".$user_id.")<br>\n"; }
        $success = true;
        if ($user_id) {  // there is a user linked to this session
            if (!$fromlogin) {
                $this->usermanager->init($this);
            }
            $success = $this->usermanager->loaduser($user_id,$this->userfields,$roles,$this->isadmin, $numrows,false,$trace);
            if ($success) { // this user can do nothing
                if ((count($roles) == 0) && !$this->isadmin) { // this user can do nothing
                   $success = false;
                   $errormessage = "You have not been assigned to any roles. Please contact the manager.";
                } else {
                    $this->greeting = $this->userfields["given_name"]!==""?$this->userfields["given_name"]:$this->userfields["family_name"];
                    if (!$success) { //if the user does not still exist or has not loaded OK...
                        $this->clearuser();
                    } elseif (isset($user_id)) {
                        $this->user_id = $user_id;
                        // $this->user_menu_number = $this->userfields["menu_number"];
                        $this->usermanager->setrightsforuser($user_id,$numrows,$trace);
                        $this->rights = $this->usermanager->getuserrights();
                    }
                }
            } else {
               $errormessage = "Error encountered when loading the user record for user {$user_id}.";
            }
        }    
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."(success=$success user ={$this->user_id} isadmin={$this->isadmin})<br>"; }
        return $success;
     }
    protected function issessiontimedout($trace=false)     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $last  = new \DateTime($this->phpsessiontable->getfield("last_access"));
        $now   = new \DateTime(\lib\StdLib::nowf());
        $inactivity = $now->getTimestamp() - $last->getTimestamp(); // in seconds
        if ($this->trace  ) { echo gtab(-1)."Leave ".__METHOD__." inactivity = $inactivity<br>\n"; }
        return (($inactivity > $this->config["app"]["SESSION_TIMEOUT"]));
     }
    protected function insertnewsessionrecord($trace=false)     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        global $siteglobals;
        $this->phpsessiontable->setfield("id",'');
        $this->phpsessiontable->setfield("phpsession",$this->phpsession_id);
        $this->phpsessiontable->setfield("starttime",\lib\StdLib::nowf());
        $this->phpsessiontable->setfield("last_access",\lib\StdLib::nowf());
        $this->phpsessiontable->setfield("user_id","0");
        $this->phpsessiontable->setfield("client_ip",array_key_exists('REMOTE_ADDR',$_SERVER)?$_SERVER['REMOTE_ADDR']:"");
        $this->phpsessiontable->setfield("timezone",$this->config["app"]["DEFAULTTIMEZONE"]);
        $success = $this->phpsessiontable->insert($this->db) ;
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $success; 
     }
    protected function restartphpsession () {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        session_regenerate_id (true);
        $this->phpsession_id= session_id();     // we will use this php-generated id as the phpsession_id for our new session 
        $this->previous_user = $this->usermanager->getfield("UserTable","given_name");
        $this->usermanager->clear();
        $this->user_id = "";
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     } 
    public    function clearuser($trace=false){
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $this->phpsessiontable->put("user_id","0",true,$trace);    //... clear the session user
        $this->user_id = 0;
        $this->clearUserDetails();
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    protected function clearUserDetails()     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
         $this->greeting = '';
         $this->domainname = '';
         $this->position = '';
         $this->isperson = 0;
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    public    function addusertosession($user_id,$greeting,&$errormessage='',$trace=false)     {
        if ($this->trace  || $trace  ) { echo gtab(1)."Enter ".__METHOD__."(user = ".$user_id.")<br>\n"; }
        $success = false;
        if ($user_id) {  // there is a user to be linked to this session
            if ($this->loaduser($user_id,true,$errormessage,$trace)) {
                $this->user_id = $user_id;
                $this->greeting = $greeting;
                $success = $this->phpsessiontable->update("user_id={$user_id}", "phpsession = '{$this->phpsession_id}'", $numrows,$errormessage, $trace, $matchedrows,false);
            }
        }    
        if ($this->trace   || $trace ) { echo gtab(-1)."Leave ".__METHOD__."success=".$success." (this>user = ".$this->user_id.")<br>"; }
        return $success;
     }
    public    function resetdb()     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $this->db->resetconnection($this->config["app"]["DBHOST"], $this->config["app"]["DBUSERNAME"], $this->config["app"]["DBPASSWORD"], $this->config["app"]["DBNAME"]);
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    public    function isadmin()     {
        if ($this->trace ) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->isadmin;
     }
    public    function ismaintenance()     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $ismaintenance = false;
        if (!empty($this->usermanager)) $ismaintenance= $this->usermanager->ismaintenance();
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $ismaintenance ;
     }
        // public    function issuperuser()     {
        //     if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        //     $issuper = false;
        //     if (!empty($this->usermanager)) $issuper = $this->usermanager->issuperuser();
        //     if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        //     return $issuper ;
        // }
    public    function loginhasexpired(&$previous_user = "")     {
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $previous_user = $this->previous_user;
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $this->login_expired;
     }
    public    function isloggedin(&$greeting,&$loginerrormessage)     {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        if ($this->user_id) {
            $greeting = $this->greeting;
            // $domainname = $this->domainname ;
            // $position = $this->position ;
            // $isperson = $this->isperson ;
            $loggedin = true;
        } else {
            $loginerrormessage = $this->loginerrormessage;
            $loggedin = false;
        } 
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__.$this->user_id,"//".$loggedin."<br>"; }
        return $loggedin;
     }
    public    function isloginsubmit()      {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->isloginsubmit;
     }
    public    function logout($newpagenum=null)     {
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $this->restartphpsession(true);
        $this->user_id = 0;
        $this->phpsession_id = session_id();     // we will use this php-generated id as the id for our new ucmsession 
        $this->pagenum = isset($newpagenum)? $newpagenum:0;
        if (isset($this->domain)) $this->domain->clear();
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    public    function getdb()      {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->db;
     }
    public    function getallowedpages()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->allowedpages;
     }
    public    function getuserid()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."  $this->user_id<br>"; }
        return $this->user_id;
     }
    public    function getuser_menu_number()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."  $this->user_menu_number<br>"; }
        return $this->user_menu_number;
     }
    public    function getgreeting()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->greeting;
     }
    public    function getuserrfid()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->usermanager->getfield("UserTable","rfid");
     }
    public    function getLoginErrorMessage()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->loginerrormessage;
     }
    public    function getdomain()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        $domain = $this->usermanager->getdomain();
        return $domain;
     }
    public    function getsessionid()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        $sessionid= $this->phpsessiontable->getfield("id");
        return $sessionid;
     }
    public    function geterrorhandler()     {
        if ($this->trace ) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->errorhandler;
     }
    public    function getrequestdata()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->requestdata;
     }
    public    function putrequestid($id)     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        $this->requestdata['id'] = $id;
     }
    public    function getmenumanager()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->menumanager;
     }
    public    function getconfig()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->config;
     }
    public    function getpagenum()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->pagenum;
     }
    public    function putpagenum($pagenum,$param="")     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        $this->pagenum = $pagenum;
        $this->pagenumparam = $param;
     }
    // public    function getpagenumparam()     {
    //     if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
    //     return $this->pagenumparam;
    //  }
    public    function usermanager()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->usermanager;
     }
    public    function getprevpagenum () {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->frompagenum;
     }
    public    function homepage()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->homepage;
     }
    public    function pageisinuse($pagenumber = 0)     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."<br>"; }
        return $this->menumanager->pageisinuse($pagenumber);
     }    
}