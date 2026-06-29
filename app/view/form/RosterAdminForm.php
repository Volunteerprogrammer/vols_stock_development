<?php
namespace app\view\form;
use \lib\StdLib as lib;
class RosterAdminForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 25;
    protected $hintwidth   = 45;
    protected $fields      = [];
    protected $formname    = "rosteradminform";
    protected $objname     = "Roster";
    protected $parents     = [];
    protected $names       = [];

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
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
        $this->component->setwidths(30, 40, 30, true);
        $formfields  = $this->component->buildinputrow("name",             1, "", "Roster Name",                 "name",             25, 25, true,  "", "");
        $this->component->restorewidths();
        $formfields .= $this->component->renderformrow(
                         'pagenumberrow', 'pagenumberprompt', 'Page number', false,
                         '', '', '', '<span id="roster_pagenumber"></span>',
                         '', '', 'pagenumberhint', 'You will need this to create a menuitem for this roster.',
                     );
        $formfields .= $this->component->builddaterow("startdate","date","","","","",6,false,"","Start Date","",false,false);
        $formfields .= $this->component->builddaterow("enddate",  "date","","","","",7,false,"","End Date",  "",false,false);
        $formfields .= $this->component->buildinputrow("maxcolumns",       2, "", "Max Columns",       "maxcolumns",       5, 5, false, "maxcolumnshint",       "This sets the maximum number of tasks to display across the roster page.");
        $formfields .= $this->component->buildinputrow("sessiondepth",     8, "", "Session Depth",     "sessiondepth",     5, 5, false, "sessiondepthhint",     "This sets the number of sessions displayed per task.");
        $formfields .= $this->component->buildinputrow("leadtime",          4, "", "Lead Time (weeks)", "leadtime",         5, 5, false, "leadtimehint",         "This sets the number of weeks prior to a session that it is created.");
        $formfields .= $this->component->buildinputrow("publishedleadtime", 5, "", "Published Lead Time (weeks)", "publishedleadtime", 5, 5, false, "publishedleadtimehint", "This sets the number of weeks prior to a session that it is published.");
        $formfields .= $this->component->buildcheckboxrow("autoextendtasks","1","",false,3,"Auto-extend Tasks","Automatically extend task sessions when within lead time.",false,false,false);
        $this->preparecommontop();
        return $formfields;
     }
    public function formscript() {
        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,   // idselection
            true,   // adjustnamerow
            true,   // updatefields
            false,  // inclmulti
            '',     // postajaxscript
            'jQuery("#roster_pagenumber").text(jfield[9] || "");',  // postloadfieldsscript
            'jQuery("#roster_pagenumber").text("");',                // postclearfieldsscript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
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
