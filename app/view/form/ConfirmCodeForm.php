<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ConfirmCodeForm extends \fw\view\form\Form {
    private $trace= false;                
    protected $names;
    protected $requestdata ;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
   }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
    public function init($session){
        parent::init($session);
// echo __METHOD__."<br>";
        $this->requestdata = $this->session->getrequestdata();
    }
    public function render($errormessage="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        global $protocol,$siteglobals;
        $content = <<<FORM
        <div id = "loginpage" class="vertical-center">
            <form id="pwresetgetcode" method="POST" >
                    <input type="hidden" name="formname" value="pwresetgetcode">
                    <input type="hidden" name="pp" value="12" />
                    <input type="hidden" name="p" value="13" />
                <div class="clear"></div> 
                 <div id="loginmessage" class="login-message">  
                    <h1>Password Reset</h1>
                    <p>Please enter the security code:</p>
                </div> 
                <div id="loginfields" class="oneinput"> 
                    <div class="tar" style="font-size:1.4rem">Code</div><div>&nbsp</div> 
                    <div><input type="text" id="fw-securitycode" name = "securitycode" placeholder = "securitycode" class="" maxlength="10" size="10" autofocus required /></div>
                    <div id="continuebtncontainer" > 
                       <div id="loginbtn" aria-disabled="false" ><span>CONTINUE</span></div> 
                    </div>
                </div>
                <div id="loginerror">$errormessage</div>
            </form>
           <div id="loginfollowupmessage" class="login-message"> 
                <p></p>
            </div>
            <script>
                function checkrequired() {
                     return ($("#fw-securitycode").val().length >= 6 );
                } 
                function dosubmit () {
                    $("#pwresetgetcode" ).trigger( "submit" );
                }
               $(function() {
                    $("#fw-securitycode").on("change",function(){
                        if (checkrequired()) {
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
                    // $(document).on('keypress',function(e) {
                    //     if(e.which == 13 && $("#loginbtn").attr("onClick") != undefined) {
                    //         dosubmit ();
                    //     }
                    // });
                });
            </script>
        </div>
        FORM;       
        return $content;  
    }
    public function formscript() {
        return $script;
    }
    // protected function renderproblemsheader($trace=false) {
    //     if ($this->trace|| $trace) { echo "Enter ".__METHOD__." Missing fields = ";var_dump($this->missingfields);echo  "<br>\n"; }
    //     $errors = '';
    //     $loginerror = $this->session->getloginerrormessage();
    //     if (count($this->missingfields) || strlen($loginerror)) {
    //         $errors = '<div class="errorbox">'."\n";
    //         $errors.= '<div class="errorheading"><p>There\'s a problem with your login.</p>'."\n".'</div><!-- errorheading -->'."\n";
    //         if (strlen($this->loginerror)) {
    //             $errors.= '<div class="errorbody"><p>'.$loginerror.'</p>'."\n".'</div><!-- errorbody -->'."\n";
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
