<?php
namespace app\view\form;
use \lib\StdLib as lib;
class RosterAdminForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 30;
    protected $hintwidth   = 40;
    protected $fields      = [];
    protected $formname    = "rosteradminform";
    protected $objname     = "Roster";
    protected $rosterid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
     }
    public function init($session, $alldata=[], $parents='', $trace=false) {
        parent::init($session, $alldata, $parents, $trace);
        $this->rosterid = $this->requestdata["id"] ?? "";
     }
    protected function addtonames($roster) {
        $this->names[$roster["id"]] = $roster["name"];
     }
    public function initfields() {
        $this->fields = [
            "id"                => "",
            "name"              => "",
            "maxcolumns"        => "",
            "autoextendtasks"   => "",
            "leadtime"          => "",
            "publishedleadtime" => "",
            "startdate"         => "",
            "enddate"           => "",
            "sessiondepth"      => "",
        ];
     }
    public function buildinputs($rights=[], $trace=false) {
        // Captures roster id from hidden data (fnum=0) — used by postloadfieldsscript
        $formfields  = '<input type="hidden" id="rosterid" data-fnum="0" />'."\n";

        // Read-only page id display (shown when editing an existing record)
        $currentpagespan = '<span id="currentpageidlabel" style="font-weight:bold;"></span>';
        $formfields .= $this->component->renderformrow(
            'currentpageidrow','','Page ID',0,'','','currentpageidlabel',$currentpagespan,
            '','','','','','','','','','',false,'hidden'
        );

        // Page selector dropdown (shown when adding new — nondatainput: exempt from disable/clear)
        $pageoptions = '<option value="">-- select a page --</option>';
        foreach ($this->parents as $page) {
            $pageoptions .= '<option value="'.(int)$page["id"].'">'.htmlspecialchars($page["name"]).'</option>';
        }
        $pageselecthtml = '<select id="pageselector" name="page_id">'.$pageoptions.'</select>';
        $hint = empty($this->parents) ? 'No unassigned roster pages — use the field below to create one.' : 'Select an existing unassigned roster page.';
        $formfields .= $this->component->renderformrow(
            'pageselectorrow','','Page',0,'','','pageselector',$pageselecthtml,
            '','','',$hint,'','','','','','',false,'hidden nondatainput'
        );

        // Inline new page name (shown when adding new)
        $newpageinput = '<input type="text" id="new_page_name" name="new_page_name" size="40" maxlength="100" />';
        $newpagehint  = empty($this->parents) ? 'Enter a name to create a new roster page.' : 'Or enter a name here to create a new roster page instead.';
        $formfields .= $this->component->renderformrow(
            'newpagenamerow','','Or create page named',0,'','','new_page_name',$newpageinput,
            '','','',$newpagehint,'','','newpagenamerow_error','','','',false,'hidden'
        );

        // Roster fields (fnum=0 is id handled above; visible fields start at fnum=1)
        $fn = 1;
        $formfields .= $this->component->buildinputrow("name",             $fn++, "", "Roster Name",                 "name",             40, 100, true,  "", "");
        $formfields .= $this->component->buildinputrow("maxcolumns",       $fn++, "", "Max Columns",                  "maxcolumns",       5,  5,   false, "", "");
        $formfields .= $this->component->buildcheckboxrow("autoextendtasks","1","",false,$fn++,"Auto-extend Tasks","Automatically extend task sessions when within lead time.",false,false,false);
        $formfields .= $this->component->buildinputrow("leadtime",          $fn++, "", "Lead Time (weeks)",           "leadtime",          5,  5,   false, "", "");
        $formfields .= $this->component->buildinputrow("publishedleadtime", $fn++, "", "Published Lead Time (weeks)", "publishedleadtime", 5,  5,   false, "", "");
        $formfields .= $this->component->builddaterow("startdate","date","","","","",$fn++,false,"","Start Date","",false,false);
        $formfields .= $this->component->builddaterow("enddate",  "date","","","","",$fn++,false,"","End Date",  "",false,false);
        $formfields .= $this->component->buildinputrow("sessiondepth",     $fn++, "", "Session Depth",               "sessiondepth",     5,  5,   false, "", "");

        $this->preparecommontop(selecttext: $this->rosterid);
        return $formfields;
     }
    protected function newclickscript() {
        return <<<JS
            jQuery("#currentpageidrow").addClass("hidden");
            jQuery("#pageselectorrow,#newpagenamerow").removeClass("hidden");
        JS;
     }
    protected function editclickscript() {
        return <<<JS
            jQuery("#pageselectorrow,#newpagenamerow").addClass("hidden");
            jQuery("#currentpageidrow").removeClass("hidden");
            jQuery("#currentpageidlabel").text(jQuery("#rosterid").val());
        JS;
     }
    public function formscript() {
        $postloadfieldsscript = <<<JS
            jQuery("#currentpageidlabel").text(jfield[0]);
            jQuery("#currentpageidrow").removeClass("hidden");
            jQuery("#pageselectorrow,#newpagenamerow").addClass("hidden");
        JS;
        $postclearfieldsscript = <<<JS
            jQuery("#currentpageidrow").addClass("hidden");
            jQuery("#currentpageidlabel").text("");
            jQuery("#pageselectorrow,#newpagenamerow").removeClass("hidden");
            jQuery("#pageselector").val("");
            jQuery("#new_page_name").val("");
        JS;
        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,   // idselection
            true,   // adjustnamerow
            true,   // updatefields
            false,  // inclmulti
            '',     // postajaxscript
            $postloadfieldsscript,
            $postclearfieldsscript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
                if (jQuery("#hiddenid").val() == "0") {
                    if (!jQuery("#pageselector").val() && !jQuery("#new_page_name").val().trim()) {
                        jQuery("#newpagenamerow_error").html("(Select an existing page or enter a new page name.)");
                        errors++;
                    }
                }
                if (!jQuery("#name").val()) {
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
            function showhidepages() {}
        JS;
        return $script;
     }
}
