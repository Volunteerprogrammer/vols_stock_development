<?php
namespace app\view\form;
use \lib\StdLib as lib;
class MenuitemForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 30;
    protected $hintwidth = 40;
    protected $fields = [];
    protected $formname = "menuitemform";
    protected $objname = "Menuitem";
    protected $parentname = "";
    protected $parentobj = "";
    protected $pagenum;
    protected $names;
    protected $parents;
    protected $taskid;
    protected $roles;
    protected $rolerows;
    protected $menuitems;
    protected $pagenumbers;
    protected $loaddowfieldscript;
    protected $loaddowvariablescript;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init( $session,$data=[],$parents="",$trace=false,$pagenumbers=[]) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session,$data,$parents,$trace);
        $this->pagenumbers = $pagenumbers;
        if ($this->trace||$trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("id"=>"");
     }
    protected function addtonames($record){
        $this->names[$record["id"]] = $record["menucode"]." - ".$record["text"];
     }

    public function buildinputs($rights=[],$trace=false) {
        // Note the fieldnum parameter should equal the position of the field in the field array in the relevant table Class
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $parentdata = array_combine(array_column($this->parents,"id"),array_column($this->parents,"name"));
        // $button = '<div class="vols-tablecell vols-width-100 aligncenter"><div id="buildsessions" class="clickable action doitbg" style"height: auto">Build</div></div>';
        // $heading = '<div style="display:inline-grid;grid-template-columns:1fr 1fr;align-items:center;"><div style="margin-right:20px">Build sessions:  </div>'.$button.'</div>';
        // $formfields  = $this->component->rendersectionheading($heading,"","","","","","","","",true);
        $formfields  = $this->component->buildinputrow("menucode",1,"",'Menu code','Menu code',20,64,true,'','');
        $formfields  .= $this->component->buildinputrow("page_number",2,"",'Page number','e.g. 101',6,4,false,'','');
        $formfields  .= $this->component->buildinputrow("text",3,"",'Menu text','Menu text',20,64,false,'','');
        $formfields  .= $this->component->buildcheckboxrow("inactive",false,"",false,4,"Inactive");
        $formfields  .= $this->component->buildcheckboxrow("is_public",false,"",false,6,'Public page','No permissions required to access this item.');
        $formfields  .= $this->component->buildinputrow("menu_number",5,"",'Menu number','Menu number',3,3,false,'','');
        $this->preparecommontop();
        return $formfields;
     }
    public function formscript() {
        // passive validation of email and phone number when being entered
          //    Thus the SUBCLASS should do the following:
          //        1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
          //        2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific event handlers, initialise form-specific (third party) components
          //        3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
          //        4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
          //        5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
          //        6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
          //        7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
          //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
        $postloadfieldsscript = <<<JS
                        // this script is already built by the component class as it creates the DOW check boxes
                        {$this->loaddowfieldscript} 
                        // find the Recurrence period radio button to check based on the data 
                        const recurrenceval = jQuery("#recurrence").val();  // loaded from the hidden fields or set by user
                        // let radioarray =  ["Once-only","Daily","Weekly","Monthly","Yearly"];
                        // let btnnum = radioarray.indexOf(recurrenceval); 
                        const radioid = "#rb"+ recurrenceval; // this is the button to be checked
                        jQuery(radioid).prop("checked", true).trigger("click"); 
                        showoptions(recurrenceval);

                        const dailyoptionid = "#do"+jQuery("#dailyoption").val();
                        jQuery(dailyoptionid).prop("checked", true)
                        const monthoptionid = "#mo"+jQuery("#monthlyoption").val();
                        jQuery(monthoptionid).prop("checked", true)
                        const yearlyoptionid = "#yo"+jQuery("#yearlyoption").val();
                        jQuery(yearlyoptionid).prop("checked", true)

                        if (jQuery("#showrowsbtn").text() === "Show linked") {
                            jQuery("#dataspace [id^=link_role]").removeClass("hidden");
                        } else {
                            jQuery("#dataspace [id^=link_role]").addClass("hidden");
                            jQuery("#dataspace [id^=link_role] input[type='checkbox']:checked").parent().parent().removeClass("hidden");        
                        }
                        displayweeklyindex(jfield[12],jfield[32]);
                        showhidepages();

         JS;
        $postclearfieldsscript = <<<JS

                        jQuery("input[type='checkbox']").prop("checked",false);
                        $('input:radio').each(function () { $(this).prop('checked', false); });
        JS;
        $presavescript = <<<JS

                        jQuery("#formerror").html("") ;
                        {$this->loaddowvariablescript}
                        // now recover the index from the 'checked' radio to 
                        let thisval = jQuery("input[type='radio'][name='rb']:checked").val();
                        jQuery("#recurrence").val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='dayopt']:checked").val();
                        jQuery("#dailyoption")   .val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='monopt']:checked").val();
                        jQuery("#monthlyoption") .val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='yearopt']:checked").val();
                        jQuery("#yearlyoption") .val(thisval);

        JS;
        $disablescript = "";
        $onloadscript = <<<JS
                        // need to display the appropriate recurrence options based on the recurrence period
                        jQuery("input[type='radio'][name='rb']").click(function() {
                            const recurrenceval = jQuery("input[type='radio'][name='rb']:checked").val();
                            showoptions(recurrenceval);
                        }) 
                        jQuery("#buildsessions").on( "click", function(event) {
                            setallinactivestatus(1,1,0,0,0,0);
                            $("#action").val("buildsessions");
                            jQuery("#{$this->formname}").trigger("submit");
                        });
                        jQuery("#weeklyinterval").on("change", function() {
                            const interval = $(this).val();
                            const index = jQuery("#weeklyindex").val(); 
                            displayweeklyindex(interval,index);
                        });
        JS;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript,
                                    $disablescript,
                                    $onloadscript
                                    ); 
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            function ordinalwords( cardinal ) {
                const ordinals = [ 'zeroth', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'nineth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth'];
                const tens = {
                    20: 'twenty',
                    30: 'thirty',
                    40: 'forty', 
                    50: 'fifty',
                    60: 'sixty', 
                    70: 'seventy',
                    80: 'eighty', 
                    90: 'ninety',
                };
                const ordinalTens = {
                    20: 'twentieth',
                    30: 'thirtieth',
                    40: 'fortieth',
                    50: 'fiftieth', 
                    60: 'sixtieth',
                    70: 'seventieth',
                    80: 'eightieth', 
                    90: 'ninetieth',
                };

                if( cardinal <= 20 ) {                    
                    return ordinals[ cardinal ];
                }

                if( cardinal % 10 === 0 ) {
                    return ordinalTens[ cardinal ];
                }

                return tens[ cardinal - ( cardinal % 10 ) ] + ordinals[ cardinal % 10 ];
            }
            function displayweeklyindex(weeklyinterval,weeklyindex) {
                // this updates the weeklyindex dropdown with the appropriate <options> for the value of weeklyinterval
                // it's called when a record is loaded and when weeklyinterval changes 
                if (weeklyinterval > 1) {
                    let options  = '<option id="weeklyindex-0" value="0" '+(weeklyindex==0?'selected':'') +'>First</option>';
                    options += '<option id="weeklyindex-1" value="1" '+(weeklyindex==1?'selected':'') +'>Second</option>';
                    for (i=3;i<=weeklyinterval;i++) {
                        const ordinal = ordinalwords(i);
                        options += '<option id="weeklyindex-'+(i-1)+'" value="'+(i-1)+'" '+(weeklyindex==(i-1)?'selected':'') +'>'+ordinal+'</option>';
                    }
                    jQuery("#weeklyindex").html(options).removeClass("hidden");
                } else {
                    jQuery("#weeklyindex").html("").addClass("hidden");
                }
             }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#menucode").val()){
                    jQuery("#menucoderow_error").html("(This is a required field.)");
                    errors++;
                }
                const pnRaw = jQuery("#page_number").val().trim();
                const pnInt = parseInt(pnRaw, 10);
                if (pnRaw === '' || isNaN(pnInt) || String(pnInt) !== pnRaw || pnInt < -1 || pnInt > 9999) {
                    jQuery("#page_numberrow_error").html("(Must be a whole number from -1 to 9999)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
            }
            function showoptions(recurrenceval) {
                let optionblock;
                switch (recurrenceval) {
                    case "Once-only": break;
                    case "Daily": optionblock = "#dailyrecurrence"; break;
                    case "Weekly": optionblock = "#weeklyrecurrence"; break;
                    case "Monthly": optionblock = "#monthlyrecurrence"; break;
                    /* case "Yearly": optionblock = "#yearlyrecurrence"; break; */
                    default: optionblock = ""
                }
                jQuery(".periodic-ocurrence").removeClass("show").addClass("hide");
                if (optionblock !== "") {
                    jQuery(optionblock).removeClass("hide").addClass("show");
                }                 
            }
            function getchildnames() { return ["role","Roles"]}
         JS;
        return $script;
     }
}
