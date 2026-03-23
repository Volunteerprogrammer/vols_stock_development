<?php
namespace fw\view\form;
use \lib\StdLib as lib;
abstract class Form { 
    private   $trace = false;                
    protected $missingfields = array();  // array of fieldnames for required fields with no data
    protected $dataerrors = array();  // name=>errormsg pairs for all validated fields in the form
    protected $dberrors = array();  
    protected $required  = array();
    protected $trimdelim;
    protected $err_message;
    protected $promptwidth;
    protected $inputwidth;
    protected $hintwidth;
    protected $formname;
    protected $groupdelimiter = "^^";
    protected $recorddelimiter = "!!";
    protected $fielddelimiter = "|";
    protected $norecordstoedit;
    protected $session;
    protected $user_id;
    protected $errorhandler;
    protected $pagenum;
    protected $processed;
    protected $isadmin;
    protected $menumanager;
    protected $namevalue;
    public function __construct() {}
    abstract protected function render(); 
    public function init($session){
        if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        $this->session = $session;
        $this->user_id = $this->session->getuserid();
        $this->errorhandler = $this->session->geterrorhandler();
        $this->pagenum = $this->session->getpagenum();
        $this->processed =  $this->session->getprevpagenum() == $this->pagenum;
        $this->isadmin = $this->session->isadmin();
        $this->menumanager = $this->session->getmenumanager();
     }
    public function getformname() {
        if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        return $this->formname;
     }
    protected function clearerrors() {
        if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        unset($this->dataerrors);
     }
    protected function checkrequired($trace=false) { 
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; var_dump($_POST);echo  "<br>\n"; }
        if (count($this->required) > 0) {
            foreach($this->required as $field=>$val) {
                $postval = trim($_POST[$field]);
                // when there's nothing in $_POST[$field], it returns a single character ascii 160! (&nbsp;)
                while (substr($postval,0,1) == "\xA0") $postval = substr ($postval,1); 
                if ($this->trace  || $trace ) { echo 'Required: ',$field,'=>',$val,' $postval ="',$postval,'"';}
                if (strlen($postval) == 0) {
                    $this->missingfields[$field] = $val;
                    if ($this->trace || $trace) { echo  "    MISSING" ;}
                }
                if ($this->trace || $trace) {echo  "<br>\n"; };
            }
        }
        if ($this->trace || $trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
     }
    protected function documentreadyscript() {
        $script = <<<JS
                    function getfontsizetofit(inputText,fontsizepx, fontfamily, target, minsize = 6, maxsize = 20 ) {
                        let growing;
                        let count = 0;
                        let direction = 0;
                        let firstwidth = 0;
                        let canvas = document.createElement('canvas'); 
                        let context = canvas.getContext('2d'); 
                        do {             
                            let font = fontsizepx + 'px ' + fontfamily; 
                            context.font = font; 
                            let textwidth = Math.ceil(context.measureText(inputText).width);
                            if (textwidth < target && fontsizepx < maxsize && growing!==false) {
                                growing = true;
                                fontsizepx = +fontsizepx + 1 ; 
                            } else if (textwidth > target && fontsizepx > minsize && growing!==true) {
                                growing = false;
                                fontsizepx = +fontsizepx - 1; 
                            } else {
                                if (growing===1 && textwidth > target) fontsizepx = +fontsizepx - 1; 
                                if (growing===-1 && textwidth < target) fontsizepx = +fontsizepx + 1; 
                                break;    
                            }
                        } while (true);
                        canvas = null;
                        return fontsizepx;
                    }
                JS;
        return $script;
     }


    /* =================================================================================================================================================
       =================================================================================================================================================
                                                                        GENERIC  FORM  JAVASCRIPT
             The subclass should call parentscript() (see the last function, below) which generates a custom script, based on the arguments passed. 
             This resulting script is returned and incorporated into the subclass's script which is, in turn, returned to $page->render() via bodysection->render() and is then 
             incorporated into the page's <head> section, following the standard javascript defined there.
             The script generated below will require some functions to be defined by the subclass, dependent on the context.
             
             Thus the SUBCLASS should do the following:
                 1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
                 2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific event handlers, initialise form-specific (third party) components
                 3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
                 4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
                 5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
                 6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
                 7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
                 8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
       =================================================================================================================================================
       ================================================================================================================================================= */
        // private function documentreadyscript($formname,$singlerecord=false,$postajaxscript='') 
        // {
        //     $script  = '$(document).ready(function() {'."\n";
        //     if (!$singlerecord) {
        //         $script .= '    shortcut.add("Alt+Ctrl+C",function() {'."\n";
        //         $script .= '        $("#newrecord").trigger("click");'."\n";
        //         $script .= '        showeditarea()'."\n";
        //         $script .= '        addnewrecord();'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    shortcut.add("Alt+Ctrl+E",function() {'."\n";
        //         $script .= '        $("#editrecord").trigger("click");'."\n";
        //         $script .= '        showeditarea()'."\n";
        //         $script .= '        editexisting();'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    $("#recordselector").change(function(){'."\n";
        //         $script .= '        editexisting();'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    $("#radio0").click(function(){'."\n";        
        //         $script .= '        clearvalidationalerts();'."\n";
        //         $script .= '        showeditarea()'."\n";
        //         $script .= '        editexisting();'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    $("#radio1").click(function(){'."\n";
        //         $script .= '        clearvalidationalerts();'."\n";
        //         $script .= '        showeditarea()'."\n";
        //         $script .= '        addnewrecord();'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    $("#deletebutton").click(function(){;'."\n";
        //         $script .= '        $.cpdialog("AREYOUSURE", "Delete this record? You\'re sure?.",deleterecord);'."\n";
        //         $script .= '    });'."\n";
        //         $script .= '    if ($("#recordselector > option").length == 0) { '."\n";        
        //         $script .= '        if ($("#radio1").length) { '."\n"; // we have an Edit/Add options but nothing to edit
        //         $script .= '            $("#radio0").attr("disabled", true)'."\n";        
        //         $script .= '            $("#radio1").attr("checked", true)'."\n";        
        //         $script .= '            showeditarea()'."\n";
        //         $script .= '            addnewrecord();'."\n";
        //         $script .= '        } else {'."\n"; //revert to action choice
        //         $script .= '            hideeditarea()'."\n";
        //         $script .= '        }'."\n";
        //         $script .= '    } else {'."\n";
        //         $script .= '        if ($("#radio1").length) { '."\n"; // we have an Edit/Add options so hide selector until #radio0 is selected
        //         $script .= '            $("#existingselectrow").css({"visibility":"hidden","display":"none"});'."\n";
        //         $script .= '        } else {'."\n"; // we have a selection but no radio buttons so it's a NO ADD situation - go straight to editing
        //         $script .= '            showeditarea()'."\n";
        //         $script .= '            editexisting();'."\n";
        //         $script .= '        }'."\n";
        //         $script .= '    }'."\n";
        //     }
        //     $script .= '    $("#resetbutton").click(function(){'."\n";
        //     $script .= '        resetform();'."\n";
        //     $script .= '    });'."\n";
        //     $script .= '    $("#submitbutton").click(function(){;'."\n";
        //     $script .= '        formsubmit();'."\n";
        //     $script .= '    });'."\n";
        //     $script .= '    var frm = $("#'.$formname.'");'."\n";
        //     $script .= '    frm.submit(function (ev) {'."\n";
        //     $script .= '      var selectedtext = $("#recordselector > option:selected").text()'."\n";
        //     $script .= '      $.ajax({'."\n";
        //     $script .= '        type: frm.attr(\'method\'),'."\n";
        //     $script .= '        url: frm.attr(\'action\'),'."\n";
        //     $script .= '        data: frm.serialize(),'."\n";
        //     $script .= '        success: function (newdata) {'."\n"; 
        //     $script .= '           $.cpdialog("CLOSE");'."\n";
        //     $script .= '           newdata = $.trim(newdata);'."\n";
        //     $script .= '           console.log(newdata);'."\n";
        //     $script .= '           if (newdata.substr(0,5) == "ERROR") {'."\n"; //AJAX ok but problem processing the request: 
        //     $script .= '               $.cpdialog("CLOSE");'."\n";
        //     $script .= '               $.cpdialog("","FAILURE REPORTED:  ".concat(newdata.substr(6)),"","","PROBLEM");'."\n";
        //     $script .= '           } else if (newdata.substr(0,5) == "LOGIN") {'."\n"; //AJAX ok but session has timed out: 
        //     $script .= '               $.cpdialog("CLOSE");'."\n";
        //     $script .= '               console.log ("expired");'."\n";
        //     $script .=  '              showloginpopup(true,newdata.substr(5));'."\n"; 
        //     $script .= '           } else {'."\n";
        //     if ($singlerecord) {
        //         if (substr($postajaxscript,0,1) == 'X') { // 'X' as first char signifies no message is to be displayed
        //             $postajaxscript = substr($postajaxscript,1);
        //         } else { 
        //             $script .= '               if (!(newdata.substr(0,7) == "SUCCESS")) {'."\n"; 
        //             $script .= '                   $.cpdialog("","The data has been saved." ,"","","ALL GOOD");'."\n";
        //             $script .= '               }'."\n"; 
        //         } 
        //         $script .= $postajaxscript; 
        //     } else {
        //         if (strlen($postajaxscript)) {
        //             $script .= $postajaxscript; 
        //         } else  { 
        //             $script .= '               $("#recordselector").html(newdata.substr(0,newdata.indexOf("^^"))) ;'."\n"; 
        //             $script .= '               $("#js-hidden").html(newdata.substr(newdata.indexOf("^^")+2)) ;'."\n"; 
        //         }
        //         $script .= '               $("#vols-action").prop({"name":"vols-action","value":"1"})'."\n";
        //         $script .= '               if ($("#radio0").length) {'."\n"; //this means we have new/edit action radio buttons so revert to action choice only...
        //         $script .= '                   if ($("#recordselector > option").length == 0) {'."\n";  // ...unless there are no current records, so just add a new record 
        //         $script .= '                       $("#radio0").prop("disabled",true);'."\n"; 
        //         $script .= '                       showeditarea();'."\n"; 
        //         $script .= '                       addnewrecord()'."\n"; 
        //         $script .= '                   } else if (selectedtext.length ) {'."\n"; 
        //         $script .= '                       $("#recordselector > option[text=\'" + selectedtext + "\']").prop("selected", true) ;'."\n"; 
        //         $script .= '                       $("#recordselector").prop("selected", true) ;'."\n"; 
        //         $script .= '                       editexisting() ;'."\n"; 
        //         $script .= '                   } else {'."\n"; 
        //         $script .= '                       $("#radio0").prop("disabled",false);'."\n"; 
        //         $script .= '                       $("#existingselectrow").css({"visibility":"hidden","display":"none"});'."\n";
        //         $script .= '                       hideeditarea() ;'."\n"; 
        //         $script .= '                   }'."\n"; 
        //         $script .= '               }'."\n"; 
        //     } 
        //     $script .= '           }'."\n"; 
        //     $script .= '        },'."\n";
        //     $script .= '        error: function (xobj,mystatus,myerror) {'."\n";
        //     $script .= '            $.cpdialog("CLOSE");'."\n";
        //     $script .= '            $.cpdialog("","FAILURE REPORTED:  ".concat(mystatus,":  ",myerror),"","","PROBLEM");'."\n";
        //     $script .= '        }'."\n";
        //     $script .= '      });'."\n";
        //     $script .= '      ev.preventDefault();'."\n";
        //     $script .= '    });'."\n";
        //     $script .= '});'."\n";
        //     return $script;
        // }
        // private function validation($formname,$singlerecord) 
        // {
        //     $script  = 'function checkrequired(dontdoit = false){'."\n";        
        //     $script .= '  if (dontdoit == false) { ;'."\n";
        //     $script .= '    clearvalidationalerts();'."\n";
        //     $script .= '    var iempty = $("#'.$formname.'").find("input").filter(function() {'."\n";
        //     $script .= '             return this.value === "" && this.required == true && $(this).is(":hidden") == false ;'."\n";
        //     $script .= '    });'."\n";
        //     $script .= '    iempty.addClass("vols-redborder");'."\n"; //.removeClass("vols-inputborder")
        //     $script .= '    var sempty = $("#'.$formname.'").find("select").filter(function() {'."\n";
        //     $script .= '             return this.value === "" && this.required == true && $(this).is(":hidden") == false ;'."\n";
        //     $script .= '    });'."\n";
        //     $script .= '    sempty.addClass("vols-redborder");'."\n"; //.removeClass("vols-inputborder")
        //     $script .= '    return (iempty.length == 0 && sempty.length == 0);'."\n";
        //     $script .= '  } else {'."\n";
        //     $script .= '     return true'."\n";
        //     $script .= '  }'."\n";
        //     $script .= '}'."\n";

        //     $script .= 'function formisvalid(message) {'."\n";        
        //     $script .= '  var valid=true;'."\n";
        //     if (!$singlerecord) { //ie the form offers editing of multiple records. We test the name submitted against those of other records in the hidden fields
        //         $script .= '  var jfield=[];'."\n";
        //         $script .= '  var jparentrecords=[];'."\n";
        //         $script .= '  var jparentrecords= makearray("#js-parentfields","'.$this->recorddelimiter.'")'."\n"; 
        //         $script .= '  for (i = 0; i < jparentrecords.length; i++) { '."\n"; 
        //         $script .= '      var parentrecord = jparentrecords[i];'."\n";
        //         $script .= '      jfield=[];'."\n";
        //         $script .= '      while (parentrecord.indexOf("'.$this->fielddelimiter.'") > -1) {'."\n"; 
        //         $script .= '          jfield.push(parentrecord.substr(0,parentrecord.indexOf("'.$this->fielddelimiter.'")));'."\n";
        //         $script .= '          parentrecord = parentrecord.substr(parentrecord.indexOf("'.$this->fielddelimiter.'")+'.strlen($this->fielddelimiter).');'."\n";
        //         $script .= '      }'."\n";
        //         $script .= '      if (parentnamefieldnum>=0 && jfield[parentnamefieldnum] == $("#namerow input[name=\'name\']").val()) {'."\n";
        //         $script .= '          if ($("#hiddenid").val() != jfield[0]) {'."\n";
        //         $script .= '               message.text = "The name is already being used by another record. Please change the name and submit the record again."'."\n";
        //         $script .= '               $("#namerow input[name=\'name\']").addClass("vols-redborder");'."\n"; 
        //         $script .= '               valid=false;'."\n"; 
        //         $script .= '          }'."\n";   
        //         $script .= '      }'."\n";
        //         $script .= '  }'."\n";
        //     }    
        //     $script .= '   valid = valid && validateform(message);'."\n"; // this f() is defined in the form subclass file 
        //     $script .= '  return valid;'."\n";
        //     $script .= '}'."\n";
        //     return $script;
        // }
        // private function formsubmission($formname,$singlerecord,$multisubmit='') 
        // {
        //     $script  = 'function formsubmit(nochecks=false){;'."\n";
        //     $script .= '    if (nochecks || checkrequired(nochecks)){'."\n";
        //     $script .= '        var message = {text:""}'."\n";
        //     $script .= '        if (nochecks || formisvalid(message)){'."\n";
        //     $script .= '            $.cpdialog("PLEASEWAIT", "I\'m processing your request...");'."\n";
        //     if (strlen($multisubmit)) {
        //         $script .= $multisubmit; 
        //     } else {//this is a few lines of default code that assumes there are up to 3 multiselects with default names - works for most forms
        //         $script .= '            if ($("#multiselect_to").length) $("#multiselect_to option").prop(\'selected\',\'selected\');'."\n"; 
        //         $script .= '            if ($("#multiselect2_to").length) $("#multiselect2_to option").prop(\'selected\',\'selected\');'."\n";
        //         $script .= '            if ($("#multiselect3_to").length) $("#multiselect3_to option").prop(\'selected\',\'selected\');'."\n";
        //     }; 
        //     $script .= '            $("#'.$formname.'").submit();'."\n";
        //     $script .= '        } else {'."\n";
        //     $script .= '            if (message.text.length=0) message.text =  "There are problems with some fields. Please check where indicated.";'."\n";            
        //     $script .= '            $.cpdialog("",message.text,"","","Please note");'."\n";
        //     $script .= '        }'."\n";
        //     $script .= '    } else {'."\n";
        //     $script .= '        $.cpdialog("","You have to complete all required fields.","","","Please note");'."\n";
        //     $script .= '    }'."\n";
        //     $script .= '};'."\n";
        //     if (!$singlerecord) {
        //         $script .= 'function deleterecord(){;'."\n";
        //         $script .= '    $.cpdialog("PLEASEWAIT", "OK I\'m deleting that record.");'."\n";
        //         $script .= '    $("#vols-action").prop({"name":"vols-delete","value":"1"})'."\n";
        //         $script .= '    $("#'.$formname.'").submit();'."\n";
        //         $script .= '};'."\n";
        //     }
        //     return $script;
        // }
        // private function screenmanagement($formname,$objectname,$singlerecord,$adjustnamerow,$idselection,$updatefields,$inclmulti) 
        // {
        //     $script  = 'function setvalidationalert(elemname,message){'."\n";
        //     $script .= '    $("#"+elemname).addClass("vols-redborder");'."\n"; 
        //     $script .= '    $("#"+elemname+"row_errorprompt").html("&nbsp;");;'."\n"; 
        //     $script .= '    $("#"+elemname+"row_error").html(message);'."\n"; 
        //     $script .= '};'."\n";
        //     $script .= 'function clearvalidationalerts(){'."\n";
        //     $script .= '    $(".vols-errorcell, .vols-errorprompt").html("");'."\n";
        //     $script .= '    $("* input").removeClass("vols-redborder");'."\n";  //.addClass("vols-inputborder ")
        //     $script .= '    $("* select").removeClass("vols-redborder");'."\n"; //.addClass("vols-inputborder ")
        //     $script .= '};'."\n";
        //     $script .= 'function disableinputs(){'."\n";
        //     $script .= '    $("* input").prop("disabled",true);'."\n";  
        //     $script .= '    $("* select").not("#recordselector").prop("disabled",true);'."\n"; 
        //     $script .= '};'."\n";
        //     $script .= 'function enableinputs(){'."\n";
        //     $script .= '    $("* input").prop("disabled",false);'."\n";  
        //     $script .= '    $("* select").not("#recordselector").prop("disabled",false);'."\n"; 
        //     $script .= '};'."\n";
        //     $script .= 'function clearform(){'."\n";
        //     $script .= '    clearvalidationalerts();'."\n";
        //     $script .= '    $("#hiddenid").val("0")'."\n";
        //     if ($idselection) {
        //         if ($updatefields) {
        //             $script .= '      updatefields(0);'."\n";
        //         }      
        //         if ($inclmulti) {
        //             $script .= '      refreshmulti(0);'."\n";
        //         }      
        //     } else {
        //         $script .= '      displayselectedrecord("");'."\n";
        //     }
        //     $script .= '}'."\n";
        //     if (!$singlerecord) {
        //         $script .= 'function setnamerow(isnewrecord){'."\n";
        //         $script .= '    if (isnewrecord) {'."\n";
        //         $script .= '      $("#nameprompt").html("Name the new '.$objectname.'");'."\n";
        //         $script .= '    } else {'."\n";
        //         $script .= '      $("#nameprompt").html("Edit the '.$objectname.'\'s name");'."\n";
        //         $script .= '    }'."\n";
        //         $script .= '}'."\n";

        //         $script .= 'function showeditarea(){'."\n";
        //         $script .= '    if ($("#editarea").css("visibility" ) == "hidden") {'."\n";
        //         $script .= '        $("#editarea").fadeIn();'."\n";
        //         $script .= '        $("#editarea").css({"visibility":"visible","display":"block"});'."\n";
        //         $script .= '    }'."\n";
        //         $script .= '}'."\n";
        
        //         $script .= 'function hideeditarea(){'."\n";
        //         $script .= '    if ($("#editarea").css("visibility" ) == "visible") {'."\n";
        //         $script .= '        $("#radio0").prop("checked",false);'."\n";
        //         $script .= '        $("#radio1").prop("checked",false);'."\n";
        //         $script .= '        $("#editarea").fadeOut();'."\n";
        //         $script .= '        $("#editarea").css({"visibility":"hidden","display":"none"});'."\n";
        //         $script .= '    }'."\n";
        //         $script .= '}'."\n";
        //         $script .= 'function addnewrecord(){'."\n";
        //         $script .= '    if ($("#existingselectrow").css("visibility" ) == "visible") {'."\n";
        //         $script .= '        $("#existingselectrow").css({"visibility":"hidden","display":"none"});'."\n";
        //         $script .= '    }'."\n";
        //         if ($adjustnamerow) {
        //             $script .= '    setnamerow(true);'."\n";
        //         }
        //         $script .= '    clearform();'."\n";
        //         $script .= '    thisaddnewrecord();'."\n";
        //         $script .= '}'."\n";
        //     //============================================================================================= edit existing
        //         $script .= 'function editexisting(disable=false) {'."\n"; //new recordselected or radio button
        //         $script .= '    $(".vols-errorcell").html("");'."\n";
        //         $script .= '    if ($("#existingselectrow").css("visibility" ) == "hidden") {'."\n";
        //         $script .= '        $("#existingselectrow").css({"visibility":"visible","display":"block"});'."\n";
        //         $script .= '    }'."\n";
        //         if ($adjustnamerow) {
        //             $script .= '    setnamerow(false);'."\n";
        //         }
        //         $script .= '    var seloptions = $("#recordselector > option").length;'."\n";
        //         $script .= '    if (seloptions > 0) {;'."\n";
        //         $script .= '        if ($("#recordselector").prop("selectedIndex") == -1) {;'."\n";
        //         $script .= '            $("#recordselector").prop("selectedIndex",0);'."\n";
        //         $script .= '        }'."\n";
        //         $script .= '        var sel = $("#recordselector option:selected" );'."\n";
        //         $script .= '        $("#hiddenid").val(sel.val());'."\n";
        //         if ($idselection) {
        //             if ($updatefields) {
        //                 $script .= '        updatefields(sel.val());'."\n";
        //             }      
        //             if ($inclmulti) {
        //                   $script .= '      refreshmulti(sel.val());'."\n";//needs to be declared in form itself
        //             }      
        //         } else {
        //             $script .= '        displayselectedrecord(sel.text());'."\n";
        //         }
        //         // $script .= '        thiseditexisting();'."\n";
        //         $script .= '        if (disable) {disableinputs();} else (enableinputs();)'."\n";
        //         $script .= '    } else {'."\n";
        //         $script .= '        $("#radio0").attr("disabled", true)'."\n";        
        //         $script .= '        $("#radio1").attr("checked", true)'."\n";        
        //         $script .= '        showeditarea()'."\n";
        //         $script .= '        addnewrecord();'."\n";
        //         $script .= '        thisaddnewrecord();'."\n";
        //         $script .= '    }'."\n";
        //         $script .= '}'."\n";
        //     }
        //     $script .= 'function resetform(){'."\n";
        //     if (!$singlerecord) {
        //         $script .= '        document.getElementById("'.$formname.'").reset();'."\n";
        //     } else   {
        //         $script .= '    if ($("#hiddenid").val() == 0) {'."\n";
        //         $script .= '        addnewrecord()'."\n";
        //         $script .= '    } else {'."\n";
        //         $script .= '        editexisting()'."\n";
        //         $script .= '    }'."\n";
        //     } 
        //     $script .= '};'."\n";
        //     return $script;
        // }
        // private function updatemulti() 
        // {
        //     $script  = 'function updatemulti(selectedid, multi_id="", parentids="", parentschildren="", childids="", options="",defaulttoto=false) {'."\n";// when a new record is selected, populate the multiselect panels 
        //     $script .= '    var fromhtml="";'."\n";
        //     $script .= '    var tohtml="";'."\n";
        //     $script .= '    var jparentids=[];'."\n"; 
        //     $script .= '    var jparentschildids=[];'."\n";
        //     $script .= '    var jchildids=[]; '."\n";
        //     $script .= '    var jchildoptions=[];'."\n";
        //     $script .= '    var parentiddiv     = parentids.length > 0?("#"+parentids):"#js-parentids";'."\n";
        //     $script .= '    var parentchilrendiv= parentschildren.length > 0?("#"+parentschildren):"#js-parentschildren";'."\n";
        //     $script .= '    var childiddiv      = childids.length > 0?("#"+childids):"#js-childids";'."\n";
        //     $script .= '    var optionsdiv      = options.length > 0?("#"+options):"#js-options";'."\n";
        //     $script .= '    var multidiv        = multi_id.length > 0?("#"+multi_id):"#multiselect";'."\n";
        //     $script .= '  if (selectedid == 0) {'."\n";
        //     $script .= '      if (defaulttoto) {'."\n";
        //     $script .= '          tohtml = $(optionsdiv).html();'."\n";
        //     $script .= '      } else {'."\n"; 
        //     $script .= '          fromhtml = $(optionsdiv).html();'."\n";
        //     $script .= '      }'."\n"; 
        //     $script .= '  } else {'."\n"; //first take the delimited strings in the hidden divs and create js arrays from them
        //     $script .= '      var jparentids       = makearray(parentiddiv,"'.$this->recorddelimiter.'")'."\n"; 
        //     $script .= '      jindex = jparentids.indexOf(selectedid)'."\n"; // when we find selectedid in jparentids[] and we have the index to all arrays
        //     $script .= '      var jparentschildids = makearray(parentchilrendiv,"'.$this->recorddelimiter.'")'."\n"; 
        //     $script .= '      if (jparentschildids.length) {'."\n";
        //     $script .= '          jchildids        = makearray(childiddiv,"'.$this->recorddelimiter.'")'."\n"; 
        //     $script .= '          jchildoptions    = makearray(optionsdiv,"</option>",true)'."\n"; 
        //     $script .= '          jchildren = "'.$this->fielddelimiter.'".concat(jparentschildids[jindex]);'."\n";// this contains this parent's child ids, field delimited
        //     $script .= '          for (i=0;i < jchildids.length ;i++) {'."\n"; 
        //     $script .= '              option = jchildoptions[i]; '."\n"; 
        //     $script .= '              if (jchildren.indexOf("'.$this->fielddelimiter.'".concat(jchildids[i],"'.$this->fielddelimiter.'")) > -1) {'."\n"; // this childid is one of the parent's children
        //     $script .= '                  tohtml = tohtml.concat(option);'."\n"; 
        //     $script .= '              } else {'."\n"; 
        //     $script .= '                  fromhtml = fromhtml.concat(option);'."\n"; 
        //     $script .= '              }'."\n"; 
        //     $script .= '          }'."\n"; 
        //     $script .= '      } else {'."\n"; //put them all into from
        //     $script .= '          fromhtml = $(optionsdiv).html();'."\n";
        //     $script .= '      }'."\n";
        //     $script .= '   }'."\n"; 
        //     $script .= '   $(multidiv).html(fromhtml);'."\n";
        //     $script .= '   $(multidiv+"_to").html(tohtml);'."\n";
        //     $script .= '}'."\n";
        //     return $script;
        // }
        // private function datamanagement($formname,$updatefields,$inclmulti,$postclearfieldsscript,$postupdatescript) 
        // {
        //     $script = ''; 
        //     if ($updatefields || $inclmulti) {  //convert the contents of a hidden DIV into a js array. Called from updatefields() and updatemulti()
        //         $script  = 'function makearray(divid="",delimiter,leavedelimiter,data="") {'."\n";
        //         $script .= '    var jarray=[];'."\n"; 
        //         $script .= '    if ($(divid).length) {'."\n"; //otherwise data should be supplied in the data param
        //         $script .= '      data= $(divid).html()'."\n"; 
        //         $script .= '    }'."\n"; 
        //         $script .= '    if (data.length) {'."\n";
        //         $script .= '      while (data.indexOf(delimiter) > -1) {'."\n"; 
        //         $script .= '        if (leavedelimiter) {'."\n";
        //         $script .= '            jarray.push(data.substr(0,data.indexOf(delimiter)+delimiter.length));'."\n";
        //         $script .= '        } else {'."\n"; //first take the delimited strings in the hidden divs and create js arrays from them
        //         $script .= '            jarray.push(data.substr(0,data.indexOf(delimiter)));'."\n";
        //         $script .= '        }'."\n";
        //         $script .= '        data= data.substr(data.indexOf(delimiter)+delimiter.length);'."\n";
        //         $script .= '       }'."\n";
        //         $script .= '     }'."\n";
        //         $script .= '   return jarray'."\n";
        //         $script .= '}'."\n";
        //     }   
        //     if ($updatefields) {
        //         $script .= 'function updatefields(selectedid) {'."\n";// when a new record is selected, populate the fields on the form from the array
        //         $script .= '  var jparentids=[];'."\n"; 
        //         $script .= '  var jfield=[];;'."\n"; 
        //         $script .= '  var jparentfields=[];'."\n";
        //         $script .= '  if (selectedid == 0) {'."\n";
        //         $script .= '      $("#'.$formname.' input:text").val("");'."\n"; // clear all input fields    //.not(":button").not(":hidden")
        //         $script .= '      $("#'.$formname.' input:checkbox" ).prop("checked", false)  '."\n";  //checkboxes
        //         $script .= '      $("#'.$formname.' select").val("");'."\n"; // clear all select fields
        //         $script .= $postclearfieldsscript; 
        //         $script .= '  } else {'."\n"; //first take the delimited strings in the hidden divs and create js arrays from them
        //         $script .= '      var jparentids       = makearray("#js-parentids","'.$this->recorddelimiter.'")'."\n"; 
        //         $script .= '      var jparentfields    = makearray("#js-parentfields","'.$this->recorddelimiter.'")'."\n"; 
        //         $script .= '      jindex = jparentids.indexOf(selectedid)'."\n"; // when we find selectedid in jparentids[] and we have the index to all arrays
        //         $script .= '      parentfields = jparentfields[jindex];'."\n"; // parentfields contains all fields for the selected object - we convert this to an array
        //         $script .= '      while (parentfields.indexOf("'.$this->fielddelimiter.'") > -1) {'."\n"; 
        //         $script .= '          jfield.push(parentfields.substr(0,parentfields.indexOf("'.$this->fielddelimiter.'")));'."\n";
        //         $script .= '          parentfields = parentfields.substr(parentfields.indexOf("'.$this->fielddelimiter.'")+'.strlen($this->fielddelimiter).');'."\n";
        //         $script .= '      }'."\n";
        //         $script .= '      for (i=0;i < jfield.length ;i++) {'."\n"; //now populate all fields that have a fieldnumber matching an array element
        //         $script .= '         $("#'.$formname.' input:text[data-fnum=\'" + i + "\']").val(jfield[i])  '."\n";  //the fieldnumber  is in the data-fnum field
        //         $script .= '         $("#'.$formname.' input:radio[data-fnum=\'" + i + jfield[i] +"\']" ).prop("checked", true)  '."\n";  //RAOIOS the fieldnumber  is in the data-fnum field
        //         $script .= '         $("#'.$formname.' input:checkbox[data-fnum=\'" + i +"\']" ).prop("checked", jfield[i]==1)  '."\n";  //chckboxes
        //         $script .= '         $("#'.$formname.' select[data-fnum=\'" + i + "\']").val(jfield[i])  '."\n";  //the fieldnumber  is in the data-fnum field
        //         $script .= '       }'."\n"; 
        //         $script .= $postupdatescript; 
        //         $script .= '   }'."\n"; 
        //         $script .= '}'."\n";
        //     }
        //     if ($inclmulti) { 
        //         $script .= $this->updatemulti();
        //     }      
        //      return $script;
        // }
        // public function parentscript($formname='', $objectname='record', $singlerecord=false, $idselection=false, $adjustnamerow=true, $updatefields=false, $inclmulti=false, $postajaxscript='', $postupdatescript='', $postclearfieldsscript='',$trace=false,$multisubmit='') 
        // {
        //     /* parameter semantics:
        //          $formname     - obvious
        //          $objectname   - used in the header to describe the entity being maintained,
        //          $singlerecord - boolean: set true if this a single record table - e.g. system settings. If true, no record selector or add/edit radio buttons will be displayed 
        //          $idselection  - boolean: true if the records to be edited are chosen by the ID in the record selection dropdown (as opposed to the displayed text)
        //          $adjustnamerow     - boolean: instructs form to modify the name from "editing" to "adding new"...,
        //          $updatefields      - boolean: when a record is selected, call updatefields() to populate the fields on the form from the js hidden arrays 
        //          $inclmulti         - boolean: there's a multiselect on the form - you must declare refreshmulti() in the subclass      
        //          $postajaxscript         - form-specific script to run to process ajax results
        //          $postupdatescript       - form-specific script to run when form is populated in "edit" mode 
        //          $postclearfieldsscript  - form-specific script to run when form is cleared for "add" mode - e.g. to set default values
        //          $trace=false,
        //          $multisubmit   -  a form-specific script to run to add the multiselect values to the form submission (as opposed to the default code)
        //     */
        //     if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        //     $script   = $this->documentreadyscript($formname,$singlerecord,$postajaxscript);
        //     $script  .= $this->validation($formname,$singlerecord);
        //     $script  .= $this->formsubmission($formname,$singlerecord,$multisubmit);
        //     $script  .= $this->screenmanagement($formname,$objectname,$singlerecord,$adjustnamerow,$idselection,$updatefields,$inclmulti) ;
        //     $script  .= $this->datamanagement($formname,$updatefields,$inclmulti,$postclearfieldsscript,$postupdatescript); 
        //     if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        //    return $script;
        // }

}