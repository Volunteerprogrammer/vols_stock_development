<?php
namespace app\view\form;
use \lib\StdLib as lib;
class HelpAdminForm extends \fw\view\form\StdCRUDForm
{
    protected $trace       = false;
    protected $promptwidth = 20;
    protected $inputwidth  = 55;
    protected $hintwidth   = 25;
    protected $fields      = [];
    protected $formname    = "helpadminform";
    protected $objname     = "Help Content";
    protected $parentname  = "";
    protected $parentobj   = "";
    protected $pagenum;
    protected $names;
    protected $parents;
    private   $pages       = [];

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents='', $trace=false, $x='', $xx='') {
        parent::init($session, $data, $parents, $trace);
        $this->pages = is_array($parents) && isset($parents['pages']) ? $parents['pages'] : [];
    }

    public function initfields() {
        $this->fields = [
            "id"                => "",
            "page_id"           => "",
            "title"             => "",
            "content"           => "",
            "date_registered"   => "",
            "registered_by"     => "",
            "date_last_updated" => "",
            "modified_by"       => "",
        ];
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["title"];
    }

    public function buildinputs($rights=[], $trace=false) {
        // Page selector
        $pageoptions = '<option value="">-- select a page --</option>';
        foreach ($this->pages as $pid => $pname) {
            $pageoptions .= '<option value="'.(int)$pid.'">'.htmlspecialchars($pname).' ('.$pid.')</option>';
        }
        $pageselect  = '<select name="page_id" id="page_id" data-fnum="1" required>';
        $pageselect .= $pageoptions;
        $pageselect .= '</select>';

        $formfields  = $this->component->renderformrow('page_idrow', '', 'Page', true, '', '', 'page_id', $pageselect);
        $formfields .= $this->component->buildinputrow("title",      2, "", 'Title',   'Title',   40, 255, true,  '', '');
        $formfields .= $this->component->buildtextarearow("content", 3, "", 'Content', 'Content', 50, 10, 10000, true, '', '');

        // Hidden audit fields (not displayed, populated by manager on save)
        $formfields .= '<input type="hidden" name="date_registered"   data-fnum="4" id="date_registered"   value="" />';
        $formfields .= '<input type="hidden" name="registered_by"     data-fnum="5" id="registered_by"     value="" />';
        $formfields .= '<input type="hidden" name="date_last_updated" data-fnum="6" id="date_last_updated" value="" />';
        $formfields .= '<input type="hidden" name="modified_by"       data-fnum="7" id="modified_by"       value="" />';

        $this->preparecommontop(false, false, '', '', false, '');
        return $formfields;
    }

    public function formscript() {
        $postloadfieldsscript = <<<SCRIPT
            const pageVal = jQuery("#page_id").val();
            if (pageVal) { jQuery("#page_id").val(pageVal); }
        SCRIPT;
        $postclearfieldsscript = <<<SCRIPT
            jQuery("#page_id").val("");
        SCRIPT;
        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,   // idselection
            true,   // adjustnamerow
            true,   // updatefields
            false,  // inclmulti
            '',     // postajaxscript
            $postloadfieldsscript,
            $postclearfieldsscript,
            false,  // trace
            '',     // multisubmit
            ''      // presavescript
        );
        $script .= <<<JS
            function loaddataintoform(recordnum) {
                const recdata = getdata();
                jQuery("#hiddenid").val(recdata[0]);
                jQuery("#page_id").val(recdata[1]);
                jQuery("#title").val(recdata[2]);
                jQuery("#content").val(recdata[3]);
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#page_id").val()) {
                    jQuery("#page_idrow_error").html("(Required)");
                    errors++;
                }
                if (!jQuery("#title").val()) {
                    jQuery("#titlerow_error").html("(Required)");
                    errors++;
                }
                if (!jQuery("#content").val()) {
                    jQuery("#contentrow_error").html("(Required)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
