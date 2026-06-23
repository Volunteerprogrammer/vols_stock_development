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
                      . '<div id="blockref_copy" class="clickable action doitbg" style="display:none;width:fit-content;padding:0 10px;float:right;" onclick="copyblockref()">Copy</div>';
        $blockrefhint = '<span id="blockref_hint" style="display:none;">Paste into another record\'s content to include this block</span>';
        $this->component->setwidths(20, 30, 50, true);
        $formfields .= $this->component->renderformrow('blockrefrow', '', 'Block ref', false, '', '', '', $blockrefcode, '', '', '', $blockrefhint);
        $this->component->restorewidths();
        $formfields .= $this->component->buildinputrow("title",      2, "", 'Title',   'Title',   40, 255, true,  '', '');
        $this->component->setwidths(20, 75, 5, true);
        $formfields .= $this->component->buildtextarearow("content", 3, "", 'Content', 'Content', 50, 10, 10000, false, '', '');
        $this->component->restorewidths();

        // Hidden audit fields (not displayed, populated by manager on save)
        $formfields .= '<input type="hidden" name="date_registered"   data-fnum="4" id="date_registered"   value="" />';
        $formfields .= '<input type="hidden" name="registered_by"     data-fnum="5" id="registered_by"     value="" />';
        $formfields .= '<input type="hidden" name="date_last_updated" data-fnum="6" id="date_last_updated" value="" />';
        $formfields .= '<input type="hidden" name="modified_by"       data-fnum="7" id="modified_by"       value="" />';

        $currentid = $this->requestdata["id"] ?? '';
        $this->preparecommontop(false, false, '', $currentid, false, '');
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
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | condblock elseblock',
                menubar: false,
                height: 380,
                branding: false,
                setup: function(editor) {
                    function pageIsSelected() { return !!jQuery('#page_id').val(); }
                    function makePageAwareSetup(api) {
                        function update() { api.setEnabled(pageIsSelected()); }
                        update();
                        jQuery('#page_id').on('change', update);
                        editor.on('focus', update);
                        return function() { jQuery('#page_id').off('change', update); };
                    }
                    editor.ui.registry.addButton('condblock', {
                        text: 'If right…',
                        tooltip: 'Insert a section visible only to users with a specific permission',
                        onSetup: makePageAwareSetup,
                        onAction: function() { openCondBlockDialog(editor); }
                    });
                    editor.ui.registry.addButton('elseblock', {
                        text: 'Else',
                        tooltip: 'Insert an {{else}} marker inside a conditional block',
                        onSetup: makePageAwareSetup,
                        onAction: function() { editor.insertContent('<p>{{else}}</p>'); }
                    });
                    editor.on('keyup', function(e) {
                        if (e.key !== '{') return;
                        var rng = editor.selection.getRng();
                        var pre = rng.cloneRange();
                        pre.selectNodeContents(editor.getBody());
                        pre.setEnd(rng.endContainer, rng.endOffset);
                        if (!pre.toString().endsWith('{{')) return;
                        var node = rng.startContainer;
                        if (node.nodeType !== 3 || rng.startOffset < 2) return;
                        var delRng = editor.dom.createRng();
                        delRng.setStart(node, rng.startOffset - 2);
                        delRng.setEnd(node, rng.startOffset);
                        editor.selection.setRng(delRng);
                        editor.selection.setContent('');
                        openCondBlockDialog(editor);
                    });
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
            function copyblockref() {
                var t = jQuery("#blockref_display").text();
                navigator.clipboard.writeText(t).then(function() {
                    jQuery("#blockref_copy").text('Copied!');
                    setTimeout(function() { jQuery("#blockref_copy").text('Copy'); }, 1500);
                });
            }
            function openCondBlockDialog(editor) {
                var pid = jQuery('#page_id').val();
                if (!pid) {
                    editor.windowManager.alert('Please select a page before inserting a conditional block.');
                    return;
                }
                doServerRequest(0, JSON.stringify({page_id: pid}), 'help_getpageactions').then(function(resp) {
                    var actions = JSON.parse(resp);
                    if (!actions || !actions.length) {
                        editor.windowManager.alert('No permission actions are defined for this page.');
                        return;
                    }
                    var items = actions.map(function(a) { return {value: a.code, text: a.name}; });
                    editor.windowManager.open({
                        title: 'Insert conditional block',
                        body: {
                            type: 'panel',
                            items: [{
                                type: 'selectbox',
                                name: 'action_code',
                                label: 'Show this section only when user has:',
                                items: items
                            }]
                        },
                        buttons: [
                            {type: 'cancel', name: 'cancel', text: 'Cancel'},
                            {type: 'submit', name: 'submit', text: 'Insert', primary: true}
                        ],
                        onSubmit: function(api) {
                            var code = api.getData().action_code;
                            var label = (items.find(function(i) { return i.value === code; }) || {}).text || code;
                            editor.insertContent('<p>{{ifright:' + code + '}}</p><p>' + label + ' — replace with your help text</p><p>{{/ifright}}</p>');
                            api.close();
                        }
                    });
                });
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#title").val()) {
                    jQuery("#titlerow_error").html("(Required)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
