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

    protected function cancelclickscript() {
        return "if (tinymce.activeEditor) { tinymce.activeEditor.mode.set('readonly'); }";
    }

    public function formscript() {
        $postloadfieldsscript = <<<JS
            if (tinymce.activeEditor) {
                tinymce.activeEditor.setContent(jQuery("#content").val() || '');
            }
        JS;
        $postclearfieldsscript = <<<JS
            jQuery("#page_id").val("");
            if (tinymce.activeEditor) { tinymce.activeEditor.setContent(''); }
        JS;
        $presavescript = <<<JS
            if (tinymce.activeEditor) { tinymce.triggerSave(); }
        JS;
        $disablescript = <<<JS
            if (tinymce.activeEditor) { tinymce.activeEditor.mode.set('design'); }
        JS;
        $onloadscript = <<<JS
            tinymce.init({
                selector: '#content',
                plugins: 'lists link',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link',
                menubar: false,
                height: 380,
                branding: false,
                setup: function(editor) {
                    editor.on('init', function() {
                        editor.mode.set('readonly');
                    });
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
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
            $postclearfieldsscript,
            false,  // trace
            '',     // multisubmit
            $presavescript,
            $disablescript,
            $onloadscript
        );
        $script .= <<<JS
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
                if (tinymce.activeEditor) { tinymce.triggerSave(); }
                if (!jQuery("#content").val().trim()) {
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
