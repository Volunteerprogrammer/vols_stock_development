<?php
namespace app\view\form;
use \lib\StdLib as lib;
class LoginForm extends \fw\view\form\Form {
    private $trace= false;     
    private $requestdata;          
    private $config;          
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
    public function init($session,$user_id=""){
        parent::init($session);
        $this->requestdata = $this->session->getrequestdata();
        $this->config = $this->session->getconfig();
// lib::prf(P,R,$this->config);
     }
    public  function setadmindata($names,$trace=false){
     } 
    public function render($errormessage="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $content = <<<HTML
        <div id = "loginpage" class="vertical-center">
            <form id="loginform" method="POST" >
                    <input type="hidden" name="formname" value="loginform">
                    <input type="hidden" name="pp" value="{$this->pagenum}">
                    <input type="hidden" name="p" value="{$this->session->homepage()}">
                    <input type="hidden" name="pagedata" value="" >
                <div class="clear"></div> 
                <div id="loginheading" class="login-message"> 
                    <h1>{$this->config["app"]["LOGINPAGEHEADING"]}</h1>
                    <p>{$this->config["app"]["LOGINPAGESUBHEADING"]}</p>
                    <div id="loginerror">$errormessage</div>
                </div> 
                <div id="loginfields"> 
                    <div class="tar">Username</div><div>&nbsp;</div> 
                    <div><input type="text"  id="fw-loginname" name = "loginname" placeholder = "Login ID" class="" maxlength="25" size="15" autofocus required ></div>
                    <div class="tar">Password</div><div>&nbsp;</div> 

                    <div class="password-field">
                        <input type="password" id="fw-password"  name = "password"  placeholder = "password***" class=""  maxlength="25" size="15" required >
                        <span id="togglepassword" class="fa fa-eye " title="Show password"></span>
                    </div>
                    
                    <div id="continuebtncontainer" > 
                       <div id="loginbtn" aria-disabled="false" class=" clickable menu "  tabindex="0" role="button"><span>Submit</span></div> 
                    </div>
                    <div id="forgottenpwcontainer" > 
                       <div id="forgottenpw" aria-disabled="false" class="hyperlink ">Forgotten your Password?</div> 
                    </div>
                </div>
            </form>
            <div id="loginmessage" class="login-message"> {$this->config["app"]["LOGINPAGEMESSAGE"]}</div>
            <form id="newpw">
                    <input type="hidden" name="formname" value="newpw">
                    <input type="hidden" name="pp" value="1" >
                    <input type="hidden" name="p" value="11" >
            </form>
            <script>
                $("#togglepassword").click(function() {
                    const passwordInput = $("#fw-password");
                    const type = passwordInput.prop("type");
                    if (type === "password") {
                        passwordInput.prop("type", "text");
                        $("#togglepassword").removeClass("fa fa-eye").addClass("fa fa-eye-slash");
                        $("#togglepassword").prop("title","Hide password");
                    } else {
                        passwordInput.prop("type", "password");
                        $("#togglepassword").removeClass("fa fa-eye-slash").addClass("fa fa-eye");
                        $("#togglepassword").prop("title","Show password");
                    }
                });
                function checkrequired() {
                    return (($("#fw-loginname").val() !== "") && ($("#fw-password").val() !== "" ))
                } 
                $(function() {
                    $( "#forgottenpw" ).on( "click",function() {
                       $( "#newpw" ).trigger( "submit" );
                    });
                    $( "#loginbtn" ).on( "click",function() {
                        if (checkrequired()) {
                            $( "#loginform" ).trigger( "submit" );
                        }
                    });
                    $(document).on('keypress',function(e) {
                        if(e.which == 13) {
                            if (checkrequired()) {
                                $( "#loginform" ).trigger( "submit" );
                            }
                        }
                    });
                });
            </script>
        </div>
        HTML;
        return $content;  
     }
    public function formscript() {
        return $script;
     }
}
