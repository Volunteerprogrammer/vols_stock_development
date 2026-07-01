<?php
namespace fw\view\form;
use \lib\StdLib as lib;
abstract class StdCRUDForm extends Form {
    private   $trace = false;
    protected $singlerecord = false;
    protected $actionbuttons = ["new"=>1,"edit"=>1,"reset"=>1,"cancel"=>1,"delete"=>1,"save"=>1];
    protected $ids;
    protected $hiddenfields;
    protected $alldata = [];
    protected $namefield;
    protected $secondselectorname="";
    protected $requestdata;
    protected $noactionrow ="";
    protected $noselection ="";
    protected $hiddeninputs ="";
    protected $selecttext ="";
    protected $pagesubheading = "";
    protected $alltext = "A<span style='text-decoration: underline;'>L</span>L";
    protected $linkedtext = "<span style='text-decoration: underline;'>L</span>inked";
    protected $collapseicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19C3,20.11 3.9,21 5,21H19C20.11,21 21,20.11 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M16.59,15.71L12,11.12L7.41,15.71L6,14.29L12,8.29L18,14.29L16.59,15.71Z" /></svg>';
    protected $expandicon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19C3,20.11 3.9,21 5,21H19C20.11,21 21,20.11 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M7.41,8.29L12,12.88L16.59,8.29L18,9.71L12,15.71L6,9.71L7.41,8.29Z" /></svg>';
    abstract protected function initfields(); 
    abstract protected function addtonames($row);
    public    function init($session,$alldata=[],$parents='',$trace=false){
        if ($this->trace||$trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->alldata = $alldata;
        $this->component->init($this->session,$this->processed,$this->promptwidth,$this->inputwidth,$this->hintwidth,$this->recorddelimiter,$this->isadmin,$this->singlerecord);
        $this->initfields($trace);
        $this->buildhiddendatafields($trace);
        $this->parents = $parents;
        $this->requestdata = $this->session->getrequestdata();
        if ($this->trace||$trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    protected function resetwidths(){
        $this->component->setwidths($this->promptwidth,$this->inputwidth,$this->hintwidth);
     }
    protected function buildhiddendatafields($trace=false){
        if ($this->trace ||$trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $this->names = []; // these are later used to build recordselector
        foreach ($this->alldata as $record) {
            $this->addtonames($record);
            $this->ids .= $record["id"].$this->recorddelimiter;  // add the string to the array
            foreach ($record as $field=>$val) {
                if (!strpos(" created locktime lockedby ",$field)) { 
                    $this->hiddenfields .= $val.$this->fielddelimiter ;
                }  
            }
            $this->hiddenfields .= $this->recorddelimiter;
        }
        if ($this->trace ||$trace ) { echo gtab(-1)."Leave ".__METHOD__."   names ".count($this->names)." data ".count($this->alldata)."<br>"; }
     }
    protected function validatefields(&$badfields) { 
        if ($this->trace) { echo gtab(0)."Enter ".__METHOD__."<br>"; }
     }
    public    function setrequired($userdata="") {
        if ($this->trace) { echo gtab(0)."Enter ".__METHOD__."<br>"; }
        $this->required = array("name"=>"Name");
     }
    protected function preparecommontop($noactionrow=false,$noselection=false,$hiddeninputs='',$selecttext='',$trace=false,$pagesubheading="")  {
        // this is form-specific and is called from the subclass
        if ($this->trace || $trace) { echo gtab(0)."Enter ".__METHOD__."$noactionrow,$noselection,$hiddeninputs,$selecttext<br>"; }
        $this->noactionrow .= $noactionrow;
        $this->noselection .= $noselection;
        $this->hiddeninputs .= $hiddeninputs;
        $this->selecttext .= $selecttext;
        $this->pagesubheading .= $pagesubheading;
      }
    private   function checkbuttonsagainstrights($pagenum,$rights,$trace=false){
        if ($this->trace || $trace) { echo gtab(0)."Enter ".__METHOD__."<br>isadmin = $this->isadmin<br>$this->formname<br>"; }
        if (!$this->isadmin) {
            $norights = [];
            if (isset($this->actionbuttons["new"])    && (in_array($pagenum."||INSERT",$rights)==false)) { unset($this->actionbuttons["new"]);}
            if (isset($this->actionbuttons["edit"])   && (in_array($pagenum."||UPDATE",$rights)==false)) { unset($this->actionbuttons["edit"]);}
            if (isset($this->actionbuttons["delete"]) && (in_array($pagenum."||DELETE",$rights)==false)) { unset($this->actionbuttons["delete"]);}
            if (!isset($this->actionbuttons["edit"])){ 
                unset($this->actionbuttons["delete"]); //IT MAY ALREADY HAVE BEEN UNSET ABOVE
                if (!isset($this->actionbuttons["new"])) {
                    unset($this->actionbuttons["cancel"]);
                }
            }
        }
     } 
    private   function rendertop($subheading,$nextpage,$menu="",$trace=false) {
        $subheadingclass = "";
        if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
            $buttons = ["noactionrow"=>$this->noactionrow,"buttons"=>$this->actionbuttons,"noselection"=>$this->noselection,"names"=>$this->names,"selecttext"=>$this->selecttext,"secondselectorname"=>$this->secondselectorname];
            $sectionhead = ["heading"=>$this->objname." Details","headingclass"=>"vols-form-pageheading","subheading"=>$subheading,"subheadingclass"=>""];
            $top = $this->component->rendercommontopbrief($sectionhead,$buttons,$menu,$trace);
        } else {  
            $top = $this->component->rendercommontop($this->fields["id"],$this->names,$subheading,$subheadingclass,$this->actionbuttons,$nextpage,$this->noactionrow,$this->noselection,$this->selecttext,$menu,$trace,"vols-form-pageheading",$this->secondselectorname);
        }        
        return $top;
     }
    private   function renderform($rights,$nextpage,$trace=false) {
        $inputs = $this->buildinputs($rights); // this one's in the subclass. Do it first because it might call preparecommontop()
        $form = '  <form id="'.$this->formname.'" method="POST" >'."\n";
        $form .='   <input type="hidden" name="p"  value="'.$nextpage.'" />'."\n";
        $form .='   <input type="hidden" name="pp"  value="'.$this->pagenum.'" />'."\n";
        $form .='   <input type="hidden" name="action" id="action"  value="" />'."\n";
        $form .='   <input type="hidden" name="formname" value="'.$this->formname.'" />'."\n";
        $form .='   <input type="hidden" name="id" id="hiddenid" value="'.$this->fields["id"].'" />'."\n";
        $form .=    $this->hiddeninputs;  // any hidden input fields required by the form 
        $form .='   <div id="dataspace">'.$inputs."</div>";
        $form .='   <div id="formerror" class="vols-shallow-table-row  vols-errorcell"></div>'."\n";
        $form .='   <div style="clear: both;"></div>'."\n";
        $form .='  </form>'."\n";
        return $form;
     }
    private   function renderhidden($trace=false) {
        $hidden  = '<div id="js-dataids">'.$this->ids.'</div>'."\n";
        $hidden .= '<div id="js-datafields">'.$this->hiddenfields.'</div>'."\n";
        $hidden .= $this->addtohidden(); // subclass might want to store non-standard/non-parent data to the form
        $hidden = '<div id="js-hidden" style="visibility: hidden; display: none;">'.$hidden.'</div>'."\n";
        return $hidden;
     }
    public    function render($pagenum='',$nextpage='',$subheading="",$rights=[],$isadmin=false,$menu="",$trace=false) {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__."$this->selecttext<br>"; }
        $this->component->setvariables($this->pagenum,$this->formname,$this->objname,$rights);
        $this->checkbuttonsagainstrights($pagenum,$rights);
        $nextpage = ($nextpage!=''?$nextpage:$pagenum);
        // $html .= "<!-- start of inputarea -->";
        $inputs ='<!--'.$this->formname.' --><div id="inputarea" class="vols-table vols-table-border">'."\n"; //vols-table-minheight
        $inputs .='  <div id="editarea" class="vols-tablebody vols-form-editarea">'."\n";
        $inputs .=     $this->renderform($rights,$nextpage,$trace);
        $inputs .=     $this->renderhidden($trace);
        $inputs .="    <script>".$this->formscript()."</script>";  
        $inputs .='  </div>'."\n"; //editarea
        $inputs .='</div>'."\n";
        $html = $this->rendertop(($subheading!=""?$subheading:$this->pagesubheading),$nextpage,$menu,$trace).$inputs;
        // $html .= "<!-- end of function rendertop -->";

        if ($this->trace || $trace ) { echo gtab(-1)."Leave ".__METHOD__."$this->selecttext<br>"; }
        return $html;
     }
    protected function addtohidden(){return "";}  
    protected function newclickscript(){return "";} //can be overwritten in the subclass  
    protected function editclickscript(){return "";} //can be overwritten in the subclass  
    protected function cancelclickscript(){return "";} //can be overwritten in the subclass  
    protected function resetclickscript(){return "";} //can be overwritten in the subclass  
/*==================================================================================================*/
    private   function vols_documentreadyscript($formname,$presavescript='',$postajaxscript='',$disablescript='',$onloadscript='') {
        $script = "jQuery(function () {\n";
        if (!$this->singlerecord)  {
            $script .= <<<JS
                    jQuery(".vol-form-headingcontainer .headingrowwrap .groupsvgcontainer").on("click",function(event){
                        changecollapsedstate(event);
                    });
                    jQuery(".vol-form-headingcontainer .headingrowwrap .addsvgcontainer").on("click",function(event){
                        addtogroup(event);   // implemented in the subclasss
                    });
                    // open all groups by default
                    expandgroup(".headingrowwrap .groupsvgcontainer");
                    jQuery("#recordselector").change(function(){
                        loadrecordfromhiddendata();
                    })
                    jQuery("#editrecord").on( "click", function(event) {
                        disableallinputstatus(false);
                        // loadrecordfromhiddendata();
                        $("#action").val("save");
                        disactivateactionbuttons(1,1,1,0,1,0);
                        disableaninputstatus("#password",true);
            JS;
            // the following is the first implementation of callback functionality designed to replace the original design
            // under which the subclasses passed blocks of script to this base class as arguements to $this->vols_masterscript() - see below
            // if ($this->formname == "clientform") {  
            if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
                $script .= "                disablescript();"; // callback to function declared in subclass
            } else {
                $script .= $disablescript; // include script passed in from subclass
            }
            $script .= <<<JS
                        {$this->editclickscript()}
                        jQuery("#dataspace input").first().focus();
                    });
                    jQuery("#newrecord").on( "click", function(event) {
                        clearform();
                        jQuery("#action").val("save");
                        jQuery("#hiddenid").val("0");
                        disactivateactionbuttons(1,1,1,0,1,0);
                        disableallinputstatus(false);
                        {$disablescript}
                        {$this->newclickscript()}
                        jQuery("#dataspace").scrollTop(0);
                        jQuery("#dataspace input").first().focus();
                    });
                    jQuery("#cancelbutton").on( "click", function(event) {
                        function doCancel() {
                            loadrecordfromhiddendata();
                            disactivateactionbuttons(0,0,1,1,0,1);
                            disableallinputstatus(true);
                            {$this->cancelclickscript()}
                        }
                        if (jQuery("#resetbutton").hasClass("inactive")) {
                            doCancel();
                        } else {
                            jQuery.volsdialog("YESNO", "<p>Any changes you have made will be lost. Proceed?</p>",
                                doCancel, null, "Unsaved changes"
                            );
                        }
                    });
                    jQuery("#deletebutton").on( "click", function(event) {
                        jQuery.volsdialog("YESNO", "<p>Delete this record? Are you sure?</p>",
                            function() {
                                disactivateactionbuttons(1,1,0,0,1,0);
                                jQuery("#action").val("delete");
                                jQuery("#{$this->formname}").trigger("submit");
                            },
                            null, "Delete record"
                        );
                    });
                    jQuery("#showrowsbtn").on("click",function (){ 
                        // used when there is a section on form containing multiple child options for
                        // the current record - eg Roles available to a User (check box selection)
                        // the following function is implemented in the subclass, and it calls back to setchildselectorheadingtext()
                        // adding the apprpriate parameters
                        jQuery(this).data("state",(jQuery(this).data("state")=="all"?"linked":"all"));
                        showhidepages();
                    });

                    jQuery("input:required, textarea:required, select:required").on('blur', function() {
                        // When an input loses focus add a class to indicate it has been "touched"
                        if (this.type == "radio")  { // need to touch whole radio group and their labels
                            const elemname = $(this).attr("name");
                            const hasselector = 'input[type="radio"][name="'+elemname+'"]';
                            jQuery("label").has(hasselector).addClass("visited");
                        } else {
                            $(this).addClass("visited");
                        }
                    });

           JS;
        }
        $script .= <<<JS

                jQuery("#resetbutton").on( "click", function(event) {
                    jQuery.volsdialog("YESNO", "<p>Any changes you have made will be lost. Proceed?</p>",
                        function() {
                            loadrecordfromhiddendata();
                            setfieldinactivestatus("#resetbutton", 1);
                            {$this->resetclickscript()}
                        },
                        null, "Unsaved changes"
                    );
                });
                jQuery("#submitbutton").on( "click", function(event) {
                    if (formhaserrors()) { 
                        jQuery("input:required, textarea:required, select:required").addClass("visited");
                        jQuery.volsdialog("OKMSG", "<p>There are problems &mdash; please check the fields.</p>", null, null, "Validation errors");
                    } else {  
                        disactivateactionbuttons(1,1,0,0,1,0);

           JS;
        // the following is the first implementation of callback functionality designed to replace the original design 
        // under which the subclasses passed blocks of script to this base class as arguements to $this->vols_masterscript() - see below
        // if ($this->formname == "clientform") {  
        if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
            $script .= "                presavescript();"; // callback to function declared in subclass
        } else {
            $script .= $presavescript; // include script passed in from subclass
        }
        $script .= <<<JS

                        $("#action").val("save");
                        jQuery("#{$this->formname}").trigger("submit");
                    }
                    return false;
                });
                jQuery("#editarea input, #editarea select, #editarea textarea").on("change",function(){
                    setfieldinactivestatus ("#resetbutton",false)
                });

        JS;
        if (!$this->singlerecord) {        
            $script .= "        disableallinputstatus(true);\n";
            $script .= "        loadrecordfromhiddendata();\n";
            $script .= "        disactivateactionbuttons(0,0,1,1,0,1);\n";

        }
        // the following is the first implementation of callback functionality designed to replace the original design 
        // under which the subclasses passed blocks of script to this base class as arguements to $this->vols_masterscript() - see below
        if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
            $script .= "        onloadscript();\n"; // callback to function declared in subclass
        } else {
            $script .= $onloadscript; // include script passed in from subclass
        }
        $script .= "    });\n";
        return $script;
     }
    private   function vols_validation($formname) {
        $script = <<<SCRIPT
        SCRIPT;
        return $script;
     }
    private   function vols_formsubmission($formname,$multisubmit='') {
        $script = <<<SCRIPT
        SCRIPT;
        return $script;
     }
    private   function vols_updatemulti() {
        $script = <<<SCRIPT
        SCRIPT;
        return $script;
     }
    private   function vols_screenmanagement($formname,$objectname,$adjustnamerow,$idselection,$updatefields,$inclmulti,$postclearfieldsscript,$postloadfieldsscript) {
        $script = <<<JS
                    function flashit(selector) {
                        jQuery(selector).addClass('flash-effect');
                        setTimeout(function() {jQuery(selector).removeClass('flash-effect');},100);
                    }
                    function changecollapsedstate (event) {
                        let state;
                        const target = jQuery(event.currentTarget);
                        const ctrl = event.ctrlKey;
                        const groupname = jQuery(target).prop("id");
                        let selector = ctrl?".vol-form-headingcontainer .headingrowwrap .groupsvgcontainer":("#"+groupname);

                        if (target.hasClass("collapsed")) {
                            state = "expanded";
                            expandgroup(selector); // changes icon, removes .collapsed from selection
                        } else {
                            state = "collapsed";
                            collapsegroup(selector); // changes icon, adds .collapsed to selection
                        }
                        
                        if (ctrl) { 
                            const selector = ".vols-tablerow.grouped,.vols-shallow-table-row.grouped,.childcontainer";
                            if (state=="expanded") { // change the state of all grouped tablerows 
                                jQuery(selector).removeClass("collapsed");
                            } else {
                                jQuery(selector).addClass("collapsed");
                            }
                        } else { // change the state of just the tablerows in the target group 
                            const selector = ".vols-tablerow.grouped."+groupname+", vols-shallow-table-row.grouped."+groupname+", .childcontainer.grouped."+groupname;
                            jQuery(selector).toggleClass("collapsed");
                        }
                    
                    }
                    function expandgroup (target) {
                        jQuery(target).html('{$this->collapseicon}').removeClass("collapsed");
                    }
                    function collapsegroup (target) {
                        jQuery(target).html('{$this->expandicon}').addClass("collapsed");
                    }
                    function clearvalidationalerts(){
                        $(".vols-errorcell, .vols-errorprompt").html("");
                        $("* input").removeClass("vols-redborder");  //.addClass("vols-inputborder ")
                        $("* select").removeClass("vols-redborder"); //.addClass("vols-inputborder ")
                     };
                    function clearform(){
                        clearvalidationalerts();
                        $("#hiddenid").val("0")
                        if ($idselection) { // $ idselection
                            if ($updatefields) { // $ updatefields
                                  loadformfields(0);
                            }      
                            if ({$inclmulti}) { // $ inclmulti
                                  refreshmulti(0);
                            }      
                        } else {
                            displayselectedrecord("");
                        }
                     }
                    function loadrecordfromhiddendata() { 
                        //new recordselected  
                        $(".vols-errorcell").html("");
                        var seloptions = $("#recordselector > option").length; 
                        if (seloptions > 0) {
                            let selectedIndex = $("#recordselector").prop("selectedIndex")
                            if (selectedIndex == -1 ) { // nothing selected
                                $("#recordselector option:first").prop('selected', true);
                            }
                            var selectedoption = $("#recordselector option:selected");
                            $("#hiddenid").val(selectedoption.val());
                            if ({$idselection}) {               // $ idselection - php parameter which is form-dependant
                                if ($updatefields) {            // $ updatefields - php parameter which is form-dependant
                                    loadformfields(selectedoption.val());
                                }      
                                if ({$inclmulti}) {             // $ inclmulti - php parameter which is form-dependant
                                    refreshmulti(selectedoption.val());//needs to be declared in form itself
                                }      
                            } else { // select on another field
                                displayselectedrecord(selectedoption.text());
                            }
                        } else {
                             jQuery("#newrecord").trigger("click");
                        }
                    }
                    function loadformfields(selectedid) {// when a new record is selected, populate the fields on the form from the array
                        var jadtaids=[]; 
                        var jfield=[];; 
                        var jdatafields=[];
                        jQuery("input:required, textarea:required, select:required, label").removeClass("visited");
                        // clear all the form fields ======
                        $(".errorshowing").removeClass("errorshowing");
                        $(".vols-lookatme").removeClass("vols-lookatme");
                        $("#{$this->formname} input,#{$this->formname} textarea").not(":hidden").not(":radio").not(":checkbox").val(""); // clear all input fields    //.not(":button")
                        $("#{$this->formname} input:radio").not( "#recordselector,#recordselector option, #dataspace .nondatainput, #dataspace .nondatainput option, #dataspace .nondatainput input" ).prop("checked", false)    //radios
                        $("#{$this->formname} input:checkbox" ).not( "#recordselector,#recordselector option, #dataspace .nondatainput, #dataspace .nondatainput option, #dataspace .nondatainput input" ).prop("checked", false)    //checkboxes
                        $("#{$this->formname} select").not("#recordselector").not(".nondatainput").val(""); // clear all select fields
                        if (selectedid == 0 || selectedid == "NA") {

            JS;
        if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
            $script .= "        postclearfieldsscript();"; // callback to function declared in subclass
        } else {
            $script .= $postclearfieldsscript; // include script passed in from subclass
        }
        $script .= <<<JS

                        } else { 
                            // now populate the fields with the record data  
                            //first take the delimited strings in the hidden divs and create js arrays from them
                            var jdataids       = makearray("#js-dataids","{$this->recorddelimiter}") 
                            var jdatafields    = makearray("#js-datafields","{$this->recorddelimiter}") 
                            jindex = jdataids.indexOf(selectedid) // when we find selectedid in jdataids[] and we have the index to all arrays
                            recordfields = jdatafields[jindex]; // recordfields contains all fields for the selected object - we convert this to an array
                            jfield =  recordfields.split('{$this->fielddelimiter}');
                            for (i=0;i < jfield.length ;i++) { 
                                //now populate all fields that have a fieldnumber matching an array element
                                fnumtest = "[data-fnum='" + i + "']";
                                inputselector = "#{$this->formname}  input"+fnumtest;  
                                textareaselector = "#{$this->formname}  textarea"+fnumtest;  
                                $(inputselector).not(":radio").not(":checkbox").val(jfield[i])    //the fieldnumber is in the data-fnum field
                                $(textareaselector).not(":radio").not(":checkbox").val(jfield[i])    //the fieldnumber is in the data-fnum field
                                if(jfield[i].indexOf("'") != -1 ) {
                                    jfield[i] = jfield[i].replace("'","\\\\'");
                                }
                                try { $("#{$this->formname} input:radio[data-fnum=\'" + i + jfield[i] +"\']" ).prop("checked", true); } catch(e) {}    //RADIOS the fieldnumber is in the data-fnum field
                                $("#{$this->formname} input:checkbox[data-fnum=\'" + i +"\']" ).prop("checked", jfield[i]==1)    //checkboxes
                                $("#{$this->formname} select[data-fnum=\'" + i + "\']").val(jfield[i])    //the fieldnumber  is in the data-fnum field
                            } 
            JS;
        if ($this->formname == "clientadminform" || $this->formname == "clientvolsform") { // this is the new version with params in arrays
            $script .= "        postloadfieldsscript(selectedid);"; // callback to function declared in subclass
        } else {
            $script .= $postloadfieldsscript; // include script passed in from subclass
        }
        $script .= <<<JS

                        } // else
                    } // function
                    function disableallinputstatus (disabled = true) {
                        jQuery("#editarea input,#editarea textarea,#editarea select,#editarea fieldset,#editarea legend,#editarea datailist, #editarea optgroup, #editarea label").not( "#editarea input[type='hidden'], #recordselector,#recordselector option, #dataspace .nondatainput, #dataspace .nondatainput option, #dataspace .nondatainput input" ).prop( "disabled", disabled); 
                    }
                    function disableaninputstatus (selector,disabled = true) {
                        jQuery(selector).not("#recordselector").prop("disabled", disabled); 
                    }
                    function disactivateactionbuttons (newrec,editrec,resetchange,canceledit,deleterec,saverec) {
                        if (newrec) {
                            jQuery("#newrecord").addClass("inactive")
                        } else  {
                            jQuery("#newrecord").removeClass("inactive")
                        }
                        if (editrec)   { //need to keep the state of the Select in line with the edit button 
                            jQuery("#editrecord").addClass("inactive");
                            jQuery("#recordselector").prop('disabled', true);
                        }    else  {
                            jQuery("#editrecord").removeClass("inactive");
                            jQuery("#recordselector").prop('disabled', false);
                        }
                        if (newrec || editrec) {
                            jQuery("#dataspace .vols-tablerow .activeicon").removeClass("inactive");
                        } else  {
                            jQuery("#dataspace .vols-tablerow .activeicon").addClass("inactive");
                        }

                        if (resetchange){jQuery("#resetbutton").addClass("inactive");}  else  {jQuery("#resetbutton").removeClass("inactive");}
                        if (canceledit){jQuery("#cancelbutton").addClass("inactive");}  else  {jQuery("#cancelbutton").removeClass("inactive");}
                        if (deleterec) {jQuery("#deletebutton").addClass("inactive");}  else  {jQuery("#deletebutton").removeClass("inactive");}
                        if (saverec)   {
                            jQuery("#submitbutton").addClass("inactive");
                            jQuery("#menubutton").removeClass("inactive");
                        }  else  {
                            jQuery("#submitbutton").removeClass("inactive");
                            jQuery("#menubutton").addClass("inactive");
                        }
                    }       
                    function setfieldinactivestatus (field,inactive) {
                        if (inactive) {
                            jQuery(field).addClass("inactive")
                        } else {
                            jQuery(field).removeClass("inactive");
                        } 
                    }

                    // function showhidepages() { // may be overwritten in subclass
                    //     setchildselectorheadingtext();
                    // }
                    function setchildselectorheadingtext(otherparentname='',otherparentvalue='',filtername="",filtervals=[]) {
                        // the task here is to change the text in the child table's heading row to reflect the status.
                        // when there's a table of child rows that have another parent, there might be a selector for that parent limiting the 
                        // visibility of children independantly of the links to the record being edited here. The subclass will populate the
                        // parameters on startup, and the second of these whenever it changes. 
                        // const childnames = getchildnames(); // implemented in subclass - returns singular and plural forms
                        // const childname = childnames[0];
                        // const childrenname = childnames[1];
                        const otherparenttest = (otherparentname=="")?"":'[data-'+otherparentname+'="'+otherparentvalue+'"]';
                        const childselector = "#dataspace [id^='link_']"; // ^=starts with
                        jQuery(childselector+".vols-tablerow").addClass("hidden"); // hide everything
                        if (filtername !== "") {
                            jQuery(childselector+".vols-tablerow").filter(function() {
                                const pt = this.dataset[filtername];
                                const truth = filtervals.includes(pt);
                                return truth;
                            }).addClass("included");                               
                        } else {
                            jQuery(childselector+".vols-tablerow").addClass("included");
                        }
                        const selector = childselector+otherparenttest+".vols-tablerow.included"
                        let button = jQuery("#showrowsbtn");
                        let headingtext = button.closest(".headingrowwrap").find("#statustextspan");
                        if (button.data("state") === "linked") {
                            // show all currently selected children
                             jQuery(selector).has("input[type='checkbox']:checked").removeClass("hidden"); 
                            // update the button text for the new status    
                            button.html( "Show {$this->alltext}" );
                            // update the heading
                            headingtext.html("LINKED");
                        } else { // all
                            //show all children selected or not (filtered by otherparent if present) 
                             jQuery(selector).removeClass("hidden");        
                            // update the button text for the new status 
                            button.html( "Show {$this->linkedtext}" );
                            // update the heading
                            headingtext.html("ALL");
                            if (jQuery(childselector+otherparenttest).length) { 
                                let ot1 = jQuery(childselector+otherparenttest).last().offset().top;
                                ot1 = ot1 + jQuery(childselector+otherparenttest).last().height();
                                const ot2 = jQuery("#dataspace").height();
                                jQuery("#dataspace").animate({scrollTop: ot1 - ot2},500);
                            }
                        }
                        jQuery(childselector+".vols-tablerow").removeClass("included");
                    }
            JS;
        return $script;
     }
    private   function vols_datamanagement($updatefields,$inclmulti) {
        $script = <<<JS
            function makearray(divid="",delimiter,leavedelimiter,data="") {
                let jarray=[]; 
                if ($(divid).length) { //otherwise data should be supplied in the data param
                  data= $(divid).html();
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
                let recdelimiter = "{$this->recorddelimiter}";
                let flddelimiter = "{$this->fielddelimiter}";
                let jdatarecords=[];
                jrecords=[];// this becomes an array of records which are in turn arrays of fields
                jdatarecords = makearray("#js-datafields",recdelimiter,false); 
                for (i = 0; i < jdatarecords.length; i++) {  
                    jrecord=[];
                    let datarecord = jdatarecords[i] + flddelimiter;
                    while (datarecord.indexOf(flddelimiter) > -1) { 
                        jrecord.push(datarecord.substr(0,datarecord.indexOf(flddelimiter)));
                        datarecord = datarecord.substr(datarecord.indexOf(flddelimiter)+flddelimiter.length);
                    }
                    jrecords.push(jrecord);
                }
                return jrecords;
            }
            function getRandomInt(min=1, max=1000000) {
                const minCeiled = Math.ceil(min);
                const maxFloored = Math.floor(max);
                return Math.floor(Math.random() * (maxFloored - minCeiled) + minCeiled); // The maximum is exclusive and the minimum is inclusive
            } 
        JS;
        return $script;
     }
    protected function vols_masterscript($formname='', $objectname='record', $idselection=0, $adjustnamerow=1, $updatefields=0, $inclmulti=0, $postajaxscript='', $postloadfieldsscript='', $postclearfieldsscript='',$trace=0,$multisubmit='',$presavescript="",$disablescript="",$onloadscript="") {
        /* parameter semantics:
             $formname     - obvious
             $objectname   - used in the header to describe the entity being maintained,
             $idselection  - boolean: true if the records to be edited are chosen by the ID in the record selection dropdown (as opposed to the displayed text)
             $adjustnamerow     - boolean: instructs form to modify the name from "editing" to "adding new"...,
             $updatefields      - boolean: when a record is selected, call loadformfields() to populate the fields on the form from the js hidden arrays 
             $inclmulti         - boolean: there's a multiselect on the form - you must declare refreshmulti() in the subclass      
             $postajaxscript         - form-specific script to run to process ajax results
             $postloadfieldsscript       - form-specific script to run when form is populated in "edit" mode 
             $postclearfieldsscript  - form-specific script to run when form is cleared for "add" mode - e.g. to set default values
             $trace=false,
             $multisubmit   -  a form-specific script to run to add the multiselect values to the form submission (as opposed to the default code)
        */
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->alldata);  echo "<br>";
        $script   = $this->vols_documentreadyscript($formname,$presavescript,$postajaxscript,$disablescript,$onloadscript);
        $script  .= $this->vols_validation($formname);
        $script  .= $this->vols_formsubmission($formname,$multisubmit);
        // $script  .='  \n//>>>>($idselection)'."\n";
        $script  .= $this->vols_screenmanagement($formname,$objectname,($adjustnamerow?"1":"0"),($idselection?"1":"0"),($updatefields?"1":"0"),($inclmulti?"1":"0"),$postclearfieldsscript,$postloadfieldsscript) ;
        // $script  .="  \n//>>>>\n";
        $script  .= $this->vols_datamanagement($formname,$updatefields,$inclmulti); 
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
       return $script;
     }
}
