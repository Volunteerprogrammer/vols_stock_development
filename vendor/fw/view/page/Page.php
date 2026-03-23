<?php
namespace fw\view\page;
use \lib\StdLib as lib;
abstract class Page  
{
    protected $doctype;
    protected $bodysection;
    protected $headsection;
      
    public function __construct() 
	{
        // instantiation of the body is to be done in the subclass so appropriate body subclasses are used
    }

    public function __destruct() 
	{
    // clean up here
    }

    public function islogoutpage ($pagenum) {
        return $pagenum === $this->logoutpagenum
    }
    public function pagenum_get ($pagenum) {
        return $pagenum === $this->pagenum
    }
    public function pagenum_put ($pagenum) {
        $this->pagenum = $pagenum;
    }
    public function frompagenum_get ($pagenum) {
        return $pagenum === $this->frompage
    }



    private function selecthead ($page_num,\cypo\iface\WebSession $session=null) 
    {    // choose the head section accourding to the page number requested 
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        switch ($page_num) {
            case 431:  // RolesForm
                $this->multiselect = true;
                break;
            default: 
        } 
        $this->headsection = new \cypo\web\page\HTMLHead($session);
    }

    private function selectbody ($page_num,\cypo\iface\WebSession $session=null) {
    // choose the body section accourding to the page number requested
    if ($this->trace) { echo "Enter ".__METHOD__.$page_num."<br>"; }
    switch ($page_num) { // this top level switch processes public pages. Secure pages are processed by default...
        case 0:
        case 1:
            $this->bodysection = new \cypo\web\page\BodyHome($page_num,$session,$this->loginform,$this->errorhandler);break;     
        case 10:
            $this->bodysection = new \cypo\web\page\BodyAbout($page_num,$session,$this->loginform,$this->errorhandler); break;      
        case 11:
            $this->bodysection = new \cypo\web\page\BodyContact($page_num,$session,$this->loginform,$this->errorhandler); break;      
        case 12:
            $this->bodysection = new \cypo\web\page\BodySignUp($page_num,$session,$this->loginform,$this->errorhandler); break;      
        case 13:
            $this->bodysection = new \cypo\web\page\BodyForgottenLogin($page_num,$session,$this->loginform,$this->errorhandler); break;      
        case 14:
            $this->bodysection = new \cypo\web\page\BodyTerms($page_num,$session,$this->loginform,$this->errorhandler); break;      
        default:     // need to be logged in for the remaining pages
            if ($session->isloggedin())  {
                switch ($page_num) {
                    case 20:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\BookBikeForm)) { 
                            $this->form = new \cypo\web\form\BookBikeForm($session) ; 
                        }
                        $this->bodysection = new \cypo\web\page\BodyBookBike($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 21:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\BookOOSForm)) { 
                            $this->form = new \cypo\web\form\BookOOSForm($session) ; 
                        }
                        $this->bodysection = new \cypo\web\page\BodyOOS($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 30:
                        $this->bodysection = new \cypo\web\page\BodyBookParking($page_num,$session,$this->loginform,$this->errorhandler); break;      
                    case 40:
                    case 41: //this is the edit customer details page
                        $this->bodysection = new \cypo\web\page\BodyMyDesktop ($page_num,$session,$this->loginform,$this->errorhandler ); break; /*,$this->editform */
                    case 42:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\DomainForm)) { 
                            $this->form = new \cypo\web\form\DomainForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyDomain($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 431:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\RolesForm)) { 
                            $this->form = new \cypo\web\form\RolesForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyRoles($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 432:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\AccountsForm)) { 
                            $this->form = new \cypo\web\form\AccountsForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyAccounts($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 433:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\DepartmentForm)) { 
                            $this->form = new \cypo\web\form\DepartmentForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyDepartments($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 44:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\UsersForm)) { 
                            $this->form = new \cypo\web\form\UsersForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyUsers($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;      
                    case 451:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\BodyMyBikeGroups)) { 
                            $this->form = new \cypo\web\form\BodyMyBikeGroups($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyBikeGroups($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;          
                    case 452:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\BikesForm)) { 
                            $this->form = new \cypo\web\form\BikesForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyBikes($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;         
                    case 46:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\PortForm)) { 
                            $this->form = new \cypo\web\form\PortForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyPorts($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;            
                    case 461:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\StationForm)) { 
                            $this->form = new \cypo\web\form\StationForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyStations($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;            
                    case 471:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\SeasonsForm)) { 
                            $this->form = new \cypo\web\form\SeasonsForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMySeasons($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 472:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\FeesForm)) { 
                            $this->form = new \cypo\web\form\FeesForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyFees($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 48:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\SalesItemForm)) { 
                            $this->form = new \cypo\web\form\SalesItemForm($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMySalesItems($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;            
                    case 50:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\ReportForm )) { 
                            $this->form = new \cypo\web\form\ReportForm ($session); 
                        }
                        $this->bodysection = new \cypo\web\page\BodyMyReports($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;  
/*===== SYSTEM... */
                    case 1001:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysDomainForm)) { 
                            $this->form = new \cypo\web\form\sysDomainForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysDomain($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1002:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysSmartPegForm)) { 
                            $this->form  = new \cypo\web\form\sysSmartPegForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysSmartPeg($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1003:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysLocationForm)) { 
                            $this->form = new \cypo\web\form\sysLocationForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysLocation($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1004:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysStationForm)) { 
                            $this->form = new \cypo\web\form\sysStationForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysStation($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1005:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysPortForm)) { 
                            $this->form = new \cypo\web\form\sysPortForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysPort($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1006:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysBikeTypeForm)) { 
                            $this->form = new \cypo\web\form\sysBikeTypeForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysBikeType($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1007:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysPageForm)) { 
                            $this->form = new \cypo\web\form\sysPageForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysPage($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1008:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysSystemForm)) { 
                            $this->form = new \cypo\web\form\sysSystemForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysSystem($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1009:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysPortTypeForm)) { 
                            $this->form = new \cypo\web\form\sysPortTypeForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysPortType($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1010:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysChargeProfileForm)) { 
                            $this->form = new \cypo\web\form\sysChargeProfileForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodysysChargeProfile($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1021:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysSimulatorForm)) { 
                            $this->form = new \cypo\web\form\sysSimulatorForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodySimulator($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    case 1022:
                        if (!isset($this->form) || !($this->form instanceof \cypo\web\form\sysGridForm)) { 
                            $this->form = new \cypo\web\form\sysGridForm($session) ;
                        }
                        $this->bodysection = new \cypo\web\page\BodyGridTest($page_num,$session,$this->loginform,$this->errorhandler,$this->form ); break;           
                    default:
                        echo "ERROR:  NUMBER ".$page_num." NOT DEFINED";
                }
            }
            else { // if possible, go back to previous page and get login - otherwise go home
                if ($this->page_num <> $this->frompage_num) {
                    $this->page_num = $this->frompage_num;
                } else {
                    $this->page_num = 0;
                }
                $this->selectbody ($this->page_num,$session); //recursive call to select previous page's BODY
                $this->loginrequired = true;
                $this->nextpage_num = $page_num;  //pass to body onload
                break;     
            }
            break;
        } 
    }

    public function render(\cypo\iface\WebSession $session=null,$norights=false) 
    {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $formscript = '';   
        //foreach ($_POST as $key => $entry) {print $key . ": " . $entry . "<br>";}
        $this->selectbody ($this->page_num,$session); // do this first to catch when login is required
        $this->selecthead ($this->page_num,$session);
        $body = $this->bodysection->render($formscript);  // do this first to get the script from the form and plug that into the head section
        $head = $this->headsection->render($formscript,$this->multiselect,$this->nextpage_num,$this->loginrequired,$norights);
        // deliver the page  
        echo $this->doctype."\n";
        echo "<html>"."\n";
        echo $head;
        echo $body;
        echo "</html>\n";
        $this->errorhandler->closelog();
    }
}

