<?php
namespace app\view\body;
use \lib\StdLib as lib;
class HTMLBody extends \fw\view\body\HTMLBody 
{ 
    private   $trace = false;
    protected $pagetitle;
    protected $pageintro;
    protected $session;
    protected $page_num;
    protected $loginform;    
    protected $errorhandler;
    protected $popupmsg;
    private   $myaccountmenu;
    private   $allowedpages; 
    private   $custpopnavbar;
    private   $syspopnavbar;
    protected $form;
    protected $data;
    public function __construct(){
        if ($this->trace ) { echo gtab()."Enter ".__METHOD__."<br>\n"; }
    }
    public function __destruct() {
         if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
     }
    public function init(\fw\session\WebSession $session,\fw\view\form\Form $form,$title="") {
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->page_num = $this->session->getpagenum();
        $this->pagetitle = $title;
        $this->form = $form;
        $this->data= $this->session->getrequestdata();
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    protected function setbodytag() 
    {
        if ($this->trace) { echo gtab(0)."Enter ".__METHOD__."<br>"; }
        $this->bodytag = '<body id="body" class="body">'."\n";
     }
    public function renderfooter(){
        $footer  = "<div id='footercontainer'>";
        $footer .= '<div  class="footerspan"><span>© <span id="currentYear"></span> Woodend Neighbourhood House Inc. A0001670N &nbsp;|&nbsp;'; 
        $footer .= '<span style="text-decoration:underline;"><a href="https://www.woodendnh.org.au/privacy-policy" target="_blank" >Privacy Policy</a></span></span></div>';
        $footer .= '<div  class="footerspan"><span >Website by SarumSites</span></div>'; 
        $footer .= '<script>jQuery(function () {var currentYear = new Date().getFullYear();document.getElementById("currentYear").textContent = currentYear;})</script>';
        $footer .= '</div>';
        return $footer;
    } 
}
