<?php
namespace app\view\form;
use \lib\StdLib as lib;
class EnterNewPasswordForm extends \fw\view\form\Form {
    private $trace= false;                
    protected $active_user;
    protected $requestdata;
    protected $config;
    protected $usermanager;
    protected $rights;
    protected $nextpage;
    protected $names;
   
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
   }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
    public function init($session){
        parent::init($session);
        $this->requestdata = $this->session->getrequestdata();
        $this->config =  $this->session->getconfig();
        $this->usermanager = $this->session->usermanager();
        $this->rights = $this->usermanager->getuserrights();

        // lib::pr([],[],$this->config);
    }
    public  function setadmindata($names,$nextpage=101,$trace=true){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->names = $names;
        $this->nextpage = $nextpage;
        $this->active_user = isset($this->active_user)?$this->active_user : 0;
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
     }    
    private function changepwds ($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $selectoptions = '';
        $userselect = $this->component->renderdropdown("recordselector",1,$selectoptions,true,false,false,false,$this->names,$this->active_user,false,'vols-form-select','',$trace);
        if ($this->trace|| $trace) { echo "Leave ".__METHOD__."<br>"; }
        return "<div class='selectuserline'>Change password for {$userselect}</div>";
     }
    public function render($errormessage="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // lib::pr([],[],$this->config);
        $pwlength = $this->config["app"]["PASSWORDLENGTH"];
        $pwcontains = $this->config["app"]["PASSWORDCONTAINS"];
        $securitycode = array_key_exists("securitycode",$this->requestdata)?$this->requestdata["securitycode"]:"";
        $nextpage = isset($this->nextpage)?  $this->nextpage : 1;
        if (isset($this->names)) { //context = admin change password for other users
            $message = $this->changepwds();
            $menuinput = '<input type="hidden" name="menuid" value="changepwd" />';
            $menudiv = '<div id="menubutton"  class="clickable menu  vols-form-heading-menubutton floatright">Menu</div><div class="clearboth"></div>';
        } else {
            $message = "<p>Please enter your new password:</p>";
            $menuinput = "";
            $menudiv = "";
        }
        $content = <<<FORM
        <div id = "loginpage" class="vertical-center">
            <form id="pwresetgetpw" method="POST" >
                    <input type="hidden" name="formname" value="pwresetgetpw">
                    <input type="hidden" name="pp" value="13" />
                    <input type="hidden" name="p" value="{$nextpage}" />
                    <input type="hidden" name="active_user" value="" />
                    <input type="hidden" name="securitycode" value="{$securitycode}" />
                    {$menuinput}
                <div class="clear"></div> 
                {$menudiv}
                <div id="loginmessage" class="login-message"> 
                    <h1>Password Reset</h1>
                    {$message}
                </div> 
                <div id="loginfields" class="noerror"> 
                    <div class="tar" style="font-size:1.4rem">Enter new password</div><div>&nbsp</div> 
                    <div><input type="password" id="fw-password1" name = "password1" placeholder = "password1" class="" maxlength="25" size="25" autofocus required /></div>
                    <div class="tar" style="font-size:1.4rem">Re-enter password</div><div>&nbsp</div> 
                    <div><input type="password" id="fw-password2" name = "password2" placeholder = "password2" class="" maxlength="25" size="25" autofocus required /></div>
                    <div id="continuebtncontainer" > 
                       <div id="loginbtn" aria-disabled="false" ><span>CONTINUE</span></div> 
                    </div>
                    <div id="newpassworderror" class="login-message">$errormessage</div>
                </div>
            </form>
            <div id="loginfollowupmessage" class="login-message"> 
                <p>Passwords must be at least {$pwlength} characters long, and contain:</p>
                <ul class="passwordreq">
        FORM;
        // lib::pr($this->config["app"]);
        if (strpos($pwcontains,"U") !== false) {$content .= "<li>at least one uppercase letter</li>";}
        if (strpos($pwcontains,"L") !== false) {$content .= "<li>at least one lowercase letter</li>";}
        if (strpos($pwcontains,"D") !== false) {$content .= "<li>at least one digit (0-9)</li>";}
        if (strpos($pwcontains,"P") !== false) {$content .= "<li>at least one punctuation character</li>";}
        $upperregex =  (strpos($pwcontains,"U") === false) ? "/.*/" : "/.*[A-Z].*/";
        $lowerregex =  (strpos($pwcontains,"L") === false) ? "/.*/" : "/.*[a-z].*/";
        $digitregex =  (strpos($pwcontains,"D") === false) ? "/.*/" : "/.*[0-9].*/";
        $punctregex =  (strpos($pwcontains,"P") === false) ? "/.*/" : "/.*[^A-Za-z0-9].*/";
        $content .= <<<FORM
                </ul>
            </div>
            <script>
                let pw1, pw2;
                function checkrequired(pw1,pw2) {
                    if (checkequality(pw1,pw2)) {
                        if (checklength(pw1)) {
                            if (checkcontents(pw1)) {
                                $("#newpassworderror").html("");
                                return true;
                            } else {
                                $("#newpassworderror").html("Your passwords do not contain the required characters (see below).");
                            }
                        } else {
                            $("#newpassworderror").html("Your passwords are too short.");
                        }
                    } else if (pw1.length>0 && pw2.length>0) {
                        $("#newpassworderror").html("Your passwords are not the same.");
                    }
                    return false;
                }    
                function checkcontents(pw1) {
                    console.log({$upperregex},{$lowerregex},{$digitregex},{$punctregex});
                    const upper = new RegExp({$upperregex});// contents of $upperregex depend on config setting
                    const lower = new RegExp({$lowerregex});// contents ...
                    const digit = new RegExp({$digitregex});// contents ...
                    const punct = new RegExp({$punctregex});;// contents ...
                    return upper.test(pw1) && lower.test(pw1) && digit.test(pw1) && punct.test(pw1);
                } 
                function checkequality(pw1,pw2) {
                    pw1 = $("#fw-password1").val();
                    pw2 = $("#fw-password2").val();
                    return (pw1 !== "") && (pw1 === pw2);
                } 
                function checklength(pw1) {
                    return  (pw1.length >= {$pwlength});
                } 
                function dosubmit () {
                    $("#pwresetgetpw" ).trigger( "submit" );
                }
                $(function() {
                    $("#recordselector").trigger("change");
                    $("#fw-password1,#fw-password2").on("change",function(){
                        let pw1 = $("#fw-password1").val();
                        let pw2 = $("#fw-password2").val();
                        if (checkrequired(pw1,pw2)) {
                            $("#loginbtn").addClass('clickable');
                            $("#loginbtn").on("click",function(){ dosubmit () });
                        } else {
                            $("#loginbtn").removeClass('clickable');
                            $("#loginbtn").off("click");
                        }
                    })
                    $(document).on('keypress',function(e) {
                        if(e.which == 13 && $("#loginbtn").attr("onClick") != undefined) {
                            dosubmit ();
                        }
                    });
                });
                jQuery("#recordselector").change(function(){
                    jQuery("#pwresetgetpw input[name='active_user']").val(jQuery("#recordselector option:selected").val());
                })

            </script>
        </div>
        FORM;
        $content .= $this->menumanager->buildmenu($this->pagenum,$this->rights,$this->isadmin);

        return $content;  
    }
    public function formscript() {
        return $script;
    }
    // protected function renderproblemsheader($trace=false) {
    //     if ($this->trace|| $trace) { echo "Enter ".__METHOD__." Missing fields = ";var_dump($this->missingfields);echo  "<br>\n"; }
    //     $errors = '';
    //     $newpassworderror = $this->session->getnewpassworderrormessage();
    //     if (count($this->missingfields) || strlen($newpassworderror)) {
    //         $errors = '<div class="errorbox">'."\n";
    //         $errors.= '<div class="errorheading"><p>There\'s a problem with your newpassword.</p>'."\n".'</div><!-- errorheading -->'."\n";
    //         if (strlen($this->newpassworderror)) {
    //             $errors.= '<div class="errorbody"><p>'.$newpassworderror.'</p>'."\n".'</div><!-- errorbody -->'."\n";
    //         }
    //         if (count($this->missingfields)) {
    //             $fcount = 0;
    //             $errors.= '<div class="errorheading">You must enter :</div><div class="errorbody"><p>';
    //             while (list($var,$val) = each($this->missingfields)) {
    //                 if ($fcount != 0) {$form .= ', ';}
    //                 ++$fcount;
    //                 $errors.= $val;
    //             }
    //             $errors.= "</p>\n</div><!-- errorbody -->\n";
    //         }
    //         $errors.= "</div><!-- errorbox -->\n";
    //     }
    //     return $errors;
    // }
    
}
