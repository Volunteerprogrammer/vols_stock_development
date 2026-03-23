<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StartNewPasswordForm extends \fw\view\form\Form {
    private $trace= false;                
    protected $names;
    protected $requestdata;
    protected $nextpage;
    protected $config;

    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
   }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
    public function init($session){
        parent::init($session);
        $this->config = $this->session->getconfig(); 
        $this->requestdata = $this->session->getrequestdata();
    }
    public function render($errormessage="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        global $protocol,$siteglobals;
        $content = <<<FORM
        <div id = "loginpage" class="vertical-center">
            <form id="pwresetgetemail" method="POST" >
                    <input type="hidden" name="formname" value="pwresetgetemail">
                    <input type="hidden" name="pp" value="11" />
                    <input type="hidden" name="p" value="12" />
                <div class="clear"></div> 
                <div id="loginmessage" class="login-message"> 
                    <h1>Password Reset</h1>
                    <p>&nbsp;</p>
                    <p>FIRST, PLEASE ENTER YOUR EMAIL ADDRESS.</p><p>We will send you a security code that will allow you to reset your password.</p>
                </div> 
                <div id="loginfields" class="oneinput"> 
                    <div id="loginerror">$errormessage</div>
                    <div class="tar" style="font-size:1.4rem;width:100%;">EMAIL ADDRESS</div><div>&nbsp</div> 
                    <div class="tal"><input type="email" id="fw_email" name = "email" placeholder = "email" class="" maxlength="50" size="30" autofocus required /></div>
                    <p>&nbsp;</p>
                    <div id="continuebtncontainer" > 
                       <div id="loginbtn" aria-disabled="false" ><span>CONTINUE</span></div> 
                    </div>
                </div>
            </form>
            <div id="loginfollowupmessage" class="login-message"> 
                <p>If the email address is linked to a registered Volunteer with {$this->config["app"]["ORGANISATIONNAME"]}, you will receive an email containing a security code.</p>
            </div>
            <script>
                 function checkrequired() {
                    return true;
                    emailaddr = $("#fw_email").val();
                    return ((emailaddr.indexOf("@") != -1) && (emailaddr.indexOf(".") != -1) &&  (emailaddr.length >=5 ))
                } 
                function dosubmit () {
                    $("#pwresetgetemail" ).trigger( "submit" );
                }
               $(function() {
                    $("#loginbtn").addClass('clickable');
                    $("#loginbtn").on("click",function(){ dosubmit () });
                    $(document).on('keypress',function(e) {
                        if(e.which == 13 && $("#loginbtn").attr("onClick") != undefined) {
                            dosubmit ();
                        } else {
                            // if (checkrequired()) {
                                $("#loginbtn").addClass('clickable');
                                $("#loginbtn").on("click",function(){ dosubmit () });
                            // } else {
                            //     $("#loginbtn").removeClass('clickable');
                            //     $("#loginbtn").off("click");
                            // }
                        }
                    });
                });
            </script>
        </div>
        FORM;
        return $content;  
    }
    public function formscript() {
        return $script;
    }
}
