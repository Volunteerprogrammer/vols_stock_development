<?php
namespace app\view\form;

class UserSelectForm extends \fw\view\form\Form {
    protected $trace= false;                
    protected $session;
    protected $pagebody;

    public function __construct() {if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }}
    public function __destruct() {if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }}
    public function init($session){$this->session = $session;}
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("loginname"=>"","password"=>"");
    }
    public function process(\cypo\iface\WebSession $session) {}
    public function renderproblemsheader($trace=false) {
        if ($this->trace|| $trace) { echo "Enter ".__METHOD__." Missing fields = ";var_dump($this->missingfields);echo  "<br>\n"; }
        $errors = '';
        $loginerror = $this->session->getloginerrormessage();
        if (count($this->missingfields) || strlen($loginerror)) {
            $errors = '<div class="errorbox">'."\n";
            $errors.= '<div class="errorheading"><p>There\'s a problem with your login.</p>'."\n".'</div><!-- errorheading -->'."\n";
            if (strlen($this->loginerror)) {
                $errors.= '<div class="errorbody"><p>'.$loginerror.'</p>'."\n".'</div><!-- errorbody -->'."\n";
            }
            if (count($this->missingfields)) {
                $fcount = 0;
                $errors.= '<div class="errorheading">You must enter :</div><div class="errorbody"><p>';
                while (list($var,$val) = each($this->missingfields)) {
                    if ($fcount != 0) {$form .= ', ';}
                    ++$fcount;
                    $errors.= $val;
                }
                $errors.= "</p>\n</div><!-- errorbody -->\n";
            }
            $errors.= "</div><!-- errorbox -->\n";
        }
        return $errors;
    }
    public function render($pagenum,$errorhandler = NULL) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        global $protocol,$siteglobals;
        $content = '<div id = "loginform" class="floating">'."\n";
        $content .= '<form id="cp-loginform" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'" method="POST" >'."\n";
        $content .= '<input type="hidden" name="Login" value="Login">'."\n";
        $content .= '<input type="hidden" name="pp" value="'.$pagenum.'" />'."\n";
        $content .= '<div class="clear"></div>'."\n"; 
        $content .= '<div id="cp-login-message" class="login-message">'."\n"; 
        $content .= '<p>To <b>Rent a Bike</b> or <b>Book Parking</b>, you must be signed up with us.</p>'."\n";
        $content .= '<p>If you\'re new to CyclePort, please <a href="'.$protocol.$siteglobals["SITEURL"].'?p=12&pp='.$pagenum.'">Sign Up</a></p>'."\n";
        $content .= '<p>If you\'ve already signed up, please login:</p>'."\n";
        $content .= '</div>'."\n"; 
        $content .= '<div id="cp-login-reason" class="login-message"></div>'."\n"; 
        $content .= '<p><input type="text"  id="cp-loginname" name = "loginname" placeholder = "Login ID" class="loginforminput cp-width-50" autofocus />&nbsp;&nbsp;&nbsp;&nbsp;'."\n";
        $content .= '<input type="password" id="cp-password"  name = "password"  placeholder = "password***" class="loginforminput cp-width-50"/></p>'."\n";
        $content .= '<p><span id="cp-login-result" class="loginerror"></span></p>'."\n";
        $content .= '</form>'."\n";
        $content .= '</div>'."\n";
        return $content;  
    }
}
