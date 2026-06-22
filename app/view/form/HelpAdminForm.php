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
        $pageselect  = '<select name="page_id" id="page_id" data-fnum="1">';
        $pageselect .= $pageoptions;
        $pageselect .= '</select>';

        $this->component->setwidths(20, 30, 50, true);
        $formfields  = $this->component->renderformrow('page_idrow', '', 'Page', false, '', '', '', $pageselect, '', '', 'page_id_hint', 'Leave blank to create a shared content block');
        $this->component->restorewidths();
        $formfields .= $this->component->buildinputrow("also_covers", 8, "", 'Also covers', 'extra page IDs, comma-separated, e.g. 102,103', 40, 500, false, '', '');
        $blockrefcode = '<code id="blockref_display" style="display:none;background:#f4f4f4;padding:2px 6px;border-radius:3px;"></code>'
                      . '<div id="blockref_copy" class="clickable action doitbg" style="display:none;width:fit-content;padding:0 10px;float:right;" onclick="'
                      . 'var t=document.getElementById(\'blockref_display\').textContent;'
                      . 'navigator.clipboard.writeText(t).then(function(){'
                      . 'var b=document.getElementById(\'blockref_copy\');b.textContent=\'Copied!\';'
                      . 'setTimeout(function(){b.textContent=\'Copy\';},1500);});'
                      . '">Copy</div>';
        $blockrefhint = '<span id="blockref_hint" style="display:none;">Paste into another record\'s content to include this block</span>';
        $this->component->setwidths(20, 30, 50, true);
        $formfields .= $this->component->renderformrow('blockrefrow', '', 'Block ref', false, '', '', '', $blockrefcode, '', '', '', $blockrefhint);
        $this->component->restorewidths();
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
        return "const _ce = tinymce.get('content'); if (_ce) { _ce.mode.set('readonly'); }";
    }

    public function formscript() {
        $postloadfieldsscript = <<<JS
            const _pled = tinymce.get('content');
            if (_pled) { _pled.setContent(jQuery("#content").val() || ''); }
            const _bid = jQuery("#hiddenid").val();
            if (_bid && _bid !== '0') {
                jQuery("#blockref_display").text('{{block:' + _bid + '}}').show();
                jQuery("#blockref_copy").css('display','inline-block');
                jQuery("#blockref_hint").show();
            } else {
                jQuery("#blockref_display").hide();
                jQuery("#blockref_copy").hide();
                jQuery("#blockref_hint").hide();
            }
        JS;
        $postclearfieldsscript = <<<JS
            jQuery("#page_id").val("");
            const _pced = tinymce.get('content'); if (_pced) { _pced.setContent(''); }
            jQuery("#blockref_display").hide();
            jQuery("#blockref_copy").hide();
            jQuery("#blockref_hint").hide();
        JS;
        $presavescript = <<<JS
            const _psed = tinymce.get('content'); if (_psed) { _psed.save(); }
            jQuery("#also_covers").val(jQuery("#also_covers").val().trim().replace(/\s+/g, ''));
        JS;
        $disablescript = <<<JS
            const _dsed = tinymce.get('content'); if (_dsed) { _dsed.mode.set('design'); }
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
                if (!jQuery("#title").val()) {
                    jQuery("#titlerow_error").html("(Required)");
                    errors++;
                }
                const _fhed = tinymce.get('content');
                if (_fhed && !_fhed.getContent({format:'text'}).trim()) {
                    errors++;
                } else if (!_fhed && !jQuery("#content").val().trim()) {
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
