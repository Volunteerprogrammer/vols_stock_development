<?php
namespace app\view\form;

class UserProfileForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $formname = "userprofileform";
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    // public function init($session,$user_id=""){
    //     parent::init($session,$user_id,'');
    //     $this->component->init($this->processed,$this->promptwidth,$this->inputwidth,$this->hintwidth,$this->recorddelimiter,$this->isadmin);
    // }
    // public function initfields($userdata=[]) {
    //     if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    //     // \lib\StdLib::v($userdata);
    //     $this->fields = array(  "id"=>$userdata["id"],
    //                             "given_name"=>$userdata["given_name"],
    //                             "family_name"=>$userdata["family_name"],
    //                             "display_name"=>$userdata["display_name"],
    //                             "email"=>$userdata["email"],
    //                             "mobile"=>$userdata["mobile"],
    //                             "available"=>$userdata["available"],
    //                             "message_by"=>$userdata["message_by"]);
    //  }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>""
                                ,"given_name"=>""
                                ,"family_name"=>""
                                ,"display_name"=>""
                                ,"email"=>""
                                ,"mobile"=>""
                                ,"username"=>""
                                ,"password"=>""
                                ,"available"=>""
                                ,"message_by"=>""
                            );
     }
    protected function addtonames(&$names,$row){
        $this->names[$row["id"]] = $row["give_name"]." ".$row["family_name"];
     }                
    protected function validatefields(&$badfields) { 
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public function setrequired($userdata="") {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->required = array("given_name"=>"Given Name","family_name"=>"Family Name");
     }
    public function render($trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->norecordstoedit = 0;
        $selectionoptions = '';
        $nextpage = $this->pagemap::ROSTERPAGE;
        $subheading = "You must click <span > SAVE CHANGES </span> for changes to take effect.&nbsp; To leave without saving, choose a MENU option.";
        $subheadingclass = "";
        $actionbuttons = ["reset"=>1,"save"=>1];
        $form  = $this->component->rendercommontop($this->formname,'User',$this->fields["id"],[],$this->pagenum,$selectionoptions,$this->norecordstoedit,$subheading,$subheadingclass,$actionbuttons,$nextpage);
        $form .= $this->component->buildinputrow("given_name",1,$this->fields["given_name"],'Given Name','Given Name',20,64,true); 
        $form .= $this->component->buildinputrow("family_name",2,$this->fields["family_name"],'Family Name','Family Name',20,64,true); 
        $form .= $this->component->buildinputrow("display_name",3,$this->fields["display_name"],'Display Name','Display Name',20,64,false,0,"If not supplied, your given name will be used."); 
        $form .= $this->component->buildinputrow("email",4,$this->fields["email"],'Email','email',20,64,true); 
        $form .= $this->component->buildinputrow("mobile",5,$this->fields["mobile"],'Mobile/Phone','mobile',20,64); 
        $form .='  <input type="hidden" name="available" id="available"  value="" />'."\n";
        $x = (int) $this->fields["available"] / 1;

        $cb  = $this->component->rendercheckbox("sun",1,($x & 1),'Sun',false,6,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("mon",2,(($x >> 1) & 1),'Mon',false,7,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("tue",4,(($x >> 2) & 1),'Tue',false,8,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("wed",8,(($x >> 3) & 1),'Wed',false,9,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("thu",16,(($x >> 4) & 1),'Thu',false,10,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("fri",32,(($x >> 5) & 1),'Fri',false,11,false,'','',false,false,false);
        $cb .= $this->component->rendercheckbox("sat",64,(($x >> 6) & 1),'Sat',false,12,false,'','',false,false,false);
        $hint="This just tells us when it's OK for us contact you about helping when we are short of volunteers. There is no commitment involved.";
        $form .= $this->component->renderformrow('availablerow',"availableprompt","Days I might be available",false,'','','',$cb ,'','','',$hint,'','','','','','','');
        switch ($this->fields["message_by"]){
            case "PHONE" : $rbval=3;BREAK;
            case "EMAIL" : $rbval=2;BREAK;
            case "SMS" : $rbval=1;BREAK;
            DEFAULT : $rbval=0;
        }
        $form .='  <input type="hidden" name="message_by"  id="message_by"  value="" />'."\n";
        $buttons = [["SMS"=>1],["Email"=>2],["Phone"=>3]];
        $rb  = $this->component->renderradiobuttons("message",$buttons,$rbval,"",13,false,"messageby");
        $form .= $this->component->renderformrow('commsrow',"commsprompt","Communicate with me by",false,'','','',$rb ,'','','','','','','','','','','');
        $form .= $this->component->rendercommonbottom('User',false);
        $hidden  = '<div id="js-parentids">'.$this->user_id.'</div>'."\n";
        $hidden .= '<div id="js-parentfields">'.implode("|",$this->fields)."!!".'</div>'."\n";
        $hiddenwrap = '<div id="js-hidden" style="visibility: hidden; display: none;">'.$hidden.'</div>'."\n";
        $form  .= $hiddenwrap;
        $form .= $this->pagemap->buildmenu ($this->pagemap::USERPROFILEPAGE,$this->isadmin);
        $form  .= "<script>".$this->formscript()."</script>" ;  
        return $form;
    }
    public function formscript() {
        // passive validation of email and phone number when being entered
        $script  = <<<SCRIPT
            function displayselectedrecord() {
                // only required if the form property 'idselection' = false 
            }
            jQuery(function() {         
                jQuery("#email").on( "focus", function() {jQuery("#emailrow_error").html("")});
                jQuery("#mobile").on( "focus", function() {jQuery("#mobilerow_error").html("")});
                jQuery("#given_name").on( "blur", function() { if (jQuery(this).val()!=""){jQuery("#given_namerow_error").html("")}});
                jQuery("#family_name").on( "blur", function() { if (jQuery(this).val()!=""){jQuery("#family_namerow_error").html("")}});
                jQuery("#email").on( "blur", function() {
                  const email = String(jQuery(this).val()).toLowerCase().trim();
                  if ((email.length > 0) && !emailvalidates(email)) {
                    jQuery("#emailrow_error").html("(That\'s not a valid valid email address.");
                  }
                });
                jQuery("#mobile").on( "blur", function() {
                  const mobile = String(jQuery(this).val()).trim();
                  if ((mobile.length > 0)  && !phonevalidates(mobile)) {
                    jQuery("#mobilerow_error").html("(That\'s not a valid Australian phone number.)");
                  }
                });
                jQuery("#submitbutton").on( "click", function(event) {
                    if (formhaserrors()) { 
                        jQuery("#formerror").html("There are problems with the form - please check above.") ;
                    } else {     
                        jQuery("#formerror").html("") ;
                        const cbval=$("#sun").is(":checked")+(2*$("#mon").is(":checked"))+(4*$("#tue").is(":checked"))+(8*$("#wed").is(":checked"))+(16*$("#thu").is(":checked"))+(32*$("#fri").is(":checked"))+(64*$("#sat").is(":checked"));
                        jQuery("#available").val(cbval);
                        const mb = jQuery("input[name=\'message\']:checked").val();
                        const options=  ["","SMS","EMAIL","PHONE"] ;
                        jQuery("#message_by").val(options[mb]);;
                        jQuery("#userprofileform").trigger("submit");
                    }
                    return false;
                });
                jQuery("#resetbutton").on( "click", function(event) {
                    if (confirm("Any changes you have made will be lost. Proceed?")) {
                        loaddataintoform ()
                    } 
                });  
            });
            function phonevalidates(phoneNumber) {
                 const phonePattern = /^0[0-8]\d{8}$/g;
                 return (phonePattern.test(phoneNumber));
            };
            function emailvalidates(email) {
                 const emailPattern =  /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                 return (emailPattern.test(email)) ;
            };
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#given_name").val()){ 
                    jQuery("#given_namerow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#family_name").val()){ 
                    jQuery("#family_namerow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#email").val()){ 
                    jQuery("#emailrow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function makearray(divid="",delimiter,leavedelimiter,data="") {
                let jarray=[]; 
                if ($(divid).length) { //otherwise data should be supplied in the data param
                  data= $(divid).html() 
                } 
                if (data.length) {
                  while (data.indexOf(delimiter) > -1) { 
                    if (leavedelimiter) {
                        jarray.push(data.substr(0,data.indexOf(delimiter)+delimiter.length));
                    } else { //first take the delimited strings in the hidden divs and create js arrays from them
                        jarray.push(data.substr(0,data.indexOf(delimiter)));
                    }
                    data= data.substr(data.indexOf(delimiter)+delimiter.length);
                   }
                 }
                return jarray;
            }            
            function getdata() {
                let recdelimiter = "$this->recorddelimiter";
                let flddelimiter = "$this->fielddelimiter";
                let jparentrecords=[];
                jparentrecords= makearray("#js-parentfields",recdelimiter,false) 
                for (i = 0; i < jparentrecords.length; i++) {  
                    let parentrecord = jparentrecords[i] + flddelimiter;
                    jfield=[];
                    while (parentrecord.indexOf(flddelimiter) > -1) { 
                        jfield.push(parentrecord.substr(0,parentrecord.indexOf(flddelimiter)));
                        parentrecord = parentrecord.substr(parentrecord.indexOf(flddelimiter)+flddelimiter.length);
                    }
                    // if (parentnamefieldnum>=0 && jfield[parentnamefieldnum] == $("#namerow input[name=\'name\']").val()) {
                    //     if ($("#hiddenid").val() != jfield[0]) {
                    //         message.text = "The name is already being used by another record. Please change the name and submit the record again."
                    //         $("#namerow input[name=\'name\']").addClass("cp-redborder"); 
                    //         valid=false; 
                    //     }
                    // }   
                }
                return jfield;
            }
            function loaddataintoform () {
                const origdata = getdata();
                jQuery("#given_name").val(origdata[1]);
                jQuery("#family_name").val(origdata[2]);
                jQuery("#display_name").val(origdata[3]);
                jQuery("#email").val(origdata[4]);
                jQuery("#mobile").val(origdata[5]);

                const x = origdata[6] / 1;
                jQuery("#sun").prop("checked",Boolean(x & 1));
                jQuery("#mon").prop("checked",Boolean((x >> 1) & 1));
                jQuery("#tue").prop("checked",Boolean((x >> 2) & 1));
                jQuery("#wed").prop("checked",Boolean((x >> 3) & 1));
                jQuery("#thu").prop("checked",Boolean((x >> 4) & 1));
                jQuery("#fri").prop("checked",Boolean((x >> 5) & 1));
                jQuery("#sat").prop("checked",Boolean((x >> 6) & 1));
                let rbval = 0;
                switch (origdata[7]){
                    case "SMS" : rbval=1;break;
                    case "EMAIL" : rbval=2;break;
                    case "PHONE" : rbval=3;break;
                    default : rbval=0;
                }
                jQuery("#messageby"+rbval).prop("checked",true);

            }
        SCRIPT;
        return $script;
     }
}
