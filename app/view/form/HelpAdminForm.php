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
    private   $pageMap     = [];
    private   $pageTypeMap = [];

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents='', $trace=false, $x='', $xx='') {
        parent::init($session, $data, $parents, $trace);
        $this->pages = is_array($parents) && isset($parents['pages']) ? $parents['pages'] : [];
    }

    public function initfields() {
        $this->fields = [
            "id"          => "",
            "page_id"     => "",
            "title"       => "",
            "content"     => "",
            "also_covers" => "",
            "published"   => "",
            "pagetype"    => "",
        ];
    }

    protected function addtonames($row) {
        $suffix = $row["published"] ? '' : ' [Draft]';
        $this->names[$row["id"]] = $row["title"] . $suffix;
        $this->pageMap[(int)$row["id"]]     = ($row["page_id"] !== null && $row["page_id"] !== '') ? (int)$row["page_id"] : null;
        $this->pageTypeMap[(int)$row["id"]] = (int)($row["pagetype"] ?? 0);
    }

    public function buildinputs($rights=[], $trace=false) {
        // Page selector

        $formfields = $this->component->buildinputrow("title",      2, "", 'Title',   'Title',   40, 255, true,  '', '');

        $pageoptions = '<option value="">-- select a page --</option>';
        foreach ($this->pages as $pid => $pname) {
            $pageoptions .= '<option value="'.(int)$pid.'">'.htmlspecialchars($pname).' ('.$pid.')</option>';
        }
        $pageselect  = '<select name="page_id" id="page_id" data-fnum="1">';
        $pageselect .= $pageoptions;
        $pageselect .= '</select>';
        $this->component->setwidths(20, 35, 45, true);
        $formfields  .= $this->component->renderformrow('page_idrow', '', 'Page', false, '', '', '', $pageselect, '', '', 'page_id_hint', 'Leave blank to create a shared content block');
        $pagetypehtml  = '<select name="pagetype" id="pagetype"><option value="0">-- select a type --</option>';
        foreach ([1 => 'System', 2 => 'Roster', 3 => 'Editor', 4 => 'Reports'] as $ptv => $ptl) {
            $pagetypehtml .= '<option value="' . $ptv . '">' . $ptl . '</option>';
        }
        $pagetypehtml .= '</select>';
        $formfields  .= $this->component->renderformrow('pagetyperow', '', 'Page type', false, '', '', '', $pagetypehtml, '', '', 'pagetype_hint', 'Select a type to enable the &#8220;If right&#8230;&#8221; button for shared blocks');
        $this->component->restorewidths();

        $formfields .= $this->component->buildinputrow("also_covers", 4, "", 'Also covers', 'extra page IDs, comma-separated, e.g. 102,103', 40, 500, false, '', '');

        $this->component->setwidths(20, 30, 50, true);
        $publishcb = '<input type="checkbox" name="published" id="published" data-fnum="5" value="1" />';
        $formfields .= $this->component->renderformrow('publishedrow', '', 'Published', false, '', '', '', $publishcb, '', '', 'published_hint', 'Only published records appear in the help viewer');


        $blockrefcode = '<code id="blockref_display" style="display:none;background:#f4f4f4;padding:2px 6px;border-radius:3px;"></code>'
                      . '<div id="blockref_copy" class="clickable action doitbg" style="display:none;width:fit-content;padding:0 10px;float:right;" onclick="copyblockref()">Copy</div>';
        $blockrefhint = '<span id="blockref_hint" style="display:none;">Paste this code into another record\'s content to include this block</span>';
        $formfields .= $this->component->renderformrow('blockrefrow', '', 'Block ref', false, '', '', '', $blockrefcode, '', '', '', $blockrefhint);
        $this->component->restorewidths();
        

        $this->component->setwidths(20, 75, 5, true);
        $formfields .= $this->component->buildtextarearow("content", 3, "", 'Content', 'Content', 50, 10, 10000, false, '', '');
        $this->component->restorewidths();


        $currentid = $this->requestdata["id"] ?? '';
        $this->preparecommontop(false, false, '', $currentid, false, '');
        return $formfields;
    }

    protected function newclickscript() {
        return <<<'JS'
            jQuery("#content").val("");
            const _nce = tinymce.get('content');
            if (_nce) { _nce.setContent(''); }
            jQuery("#blockref_display").hide();
            jQuery("#blockref_copy").hide();
            jQuery("#blockref_hint").hide();
        JS;
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
            const _ptid = parseInt(_bid) || 0;
            const _ptype = _ptid ? (helpPageTypeMap[_ptid] || 0) : 0;
            jQuery('#pagetype').val(_ptype);
            jQuery('#pagetyperow').toggle(!jQuery('#page_id').val());
        JS;
        $postclearfieldsscript = <<<JS
            jQuery("#page_id").val("");
            jQuery("#content").val("");
            const _pced = tinymce.get('content'); if (_pced) { _pced.setContent(''); }
            jQuery("#blockref_display").hide();
            jQuery("#blockref_copy").hide();
            jQuery("#blockref_hint").hide();
            jQuery('#pagetype').val('0');
            jQuery('#pagetyperow').show();
        JS;
        $presavescript = <<<JS
            const _psed = tinymce.get('content');
            if (_psed) { jQuery('#content').prop('disabled', false).val(_psed.getContent()); }
            jQuery('#published').prop('disabled', false);
            jQuery('#pagetype').prop('disabled', false);
            jQuery("#also_covers").val(jQuery("#also_covers").val().trim().replace(/\s+/g, ''));
        JS;
        $disablescript = <<<JS
            const _dsed = tinymce.get('content');
            if (_dsed) { _dsed.mode.set('design'); _dsed.fire('ResizeEditor'); }
        JS;
        $onloadscript = <<<JS
            var _tinyHeight = Math.max(300, window.innerHeight - (document.getElementById('content').getBoundingClientRect().top + window.scrollY) - 90);
            tinymce.init({
                selector: '#content',
                plugins: 'lists link',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | qheading | condblock elseblock',
                menubar: false,
                width: '100%',
                height: _tinyHeight,
                branding: false,
                forced_root_block: 'p',
                invalid_styles: { 'span': 'white-space' },
                content_style: 'body { overflow-wrap: break-word; word-wrap: break-word; overflow-x: hidden; } span { white-space: normal !important; } div, p { margin: 0 0 1em 0; }',
                setup: function(editor) {
                    var _setupContent = jQuery('#content').val();
                    function pageIsSelected() { return !!jQuery('#page_id').val() || parseInt(jQuery('#pagetype').val()) > 0; }
                    function makePageAwareSetup(api) {
                        function update() {
                            var on = pageIsSelected();
                            if (typeof api.setEnabled === 'function') { api.setEnabled(on); }
                            else { api.setDisabled(!on); }
                        }
                        update();
                        jQuery('#page_id').on('change', update);
                        jQuery('#pagetype').on('change', update);
                        editor.on('focus', update);
                        return function() {
                            jQuery('#page_id').off('change', update);
                            jQuery('#pagetype').off('change', update);
                        };
                    }
                    editor.ui.registry.addButton('qheading', {
                        text: 'Heading',
                        tooltip: 'Insert {{Q}} marker at start of paragraph to create a Q&A question heading',
                        onAction: function() {
                            var node  = editor.selection.getNode();
                            var block = editor.dom.getParent(node, 'p,div,h1,h2,h3,h4,h5,h6') || node;
                            var rng   = editor.dom.createRng();
                            rng.selectNodeContents(block);
                            rng.collapse(true);
                            editor.selection.setRng(rng);
                            editor.insertContent('{{Q}} ');
                        }
                    });
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
                        if (_setupContent) { editor.setContent(_setupContent); }
                        setTimeout(function() { editor.mode.set('readonly'); }, 0);
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
        $pageMapJson     = json_encode($this->pageMap);
        $pageTypeMapJson = json_encode($this->pageTypeMap);
        $script .= <<<JS
            (function() {
                const _orig = disableallinputstatus;
                disableallinputstatus = function(disabled) {
                    _orig(disabled);
                    const _ed = tinymce.get('content');
                    if (_ed && disabled) { _ed.mode.set('readonly'); }
                };
            })();
            const helpPageMap     = {$pageMapJson};
            const helpPageTypeMap = {$pageTypeMapJson};
            function checkPageDuplicate() {
                const selectedPage = parseInt(jQuery("#page_id").val()) || null;
                const currentId = parseInt(jQuery("#hiddenid").val()) || 0;
                const errorRow = jQuery("#page_idrow_error").closest(".vols-shallow-table-row");
                if (!selectedPage) {
                    jQuery("#page_idrow_error").html("");
                    errorRow.removeClass("errorshowing");
                    return false;
                }
                const clash = Object.entries(helpPageMap).find(([id, pid]) => pid === selectedPage && parseInt(id) !== currentId);
                if (clash) {
                    jQuery("#page_idrow_error").html("This page already has a help record");
                    errorRow.addClass("errorshowing");
                    return true;
                }
                jQuery("#page_idrow_error").html("");
                errorRow.removeClass("errorshowing");
                return false;
            }
            jQuery(function() {
                jQuery("#page_id").on("change", checkPageDuplicate);
                jQuery("#page_id").on("change", function() {
                    var _hasPid = !!jQuery(this).val();
                    jQuery('#pagetyperow').toggle(!_hasPid);
                    if (_hasPid) { jQuery('#pagetype').val('0'); }
                });
            });
            function copyblockref() {
                var t = jQuery("#blockref_display").text();
                navigator.clipboard.writeText(t).then(function() {
                    jQuery("#blockref_copy").text('Copied!');
                    setTimeout(function() { jQuery("#blockref_copy").text('Copy'); }, 1500);
                });
            }
            function openCondBlockDialog(editor) {
                var pid   = jQuery('#page_id').val();
                var ptype = parseInt(jQuery('#pagetype').val()) || 0;
                var payload;
                if (pid) {
                    payload = {page_id: pid};
                } else if (ptype > 0) {
                    payload = {pagetype: ptype};
                } else {
                    editor.windowManager.alert('Please select a page or page type before inserting a conditional block.');
                    return;
                }
                doServerRequest(0, JSON.stringify(payload), 'help_getpageactions').then(function(resp) {
                    var actions = JSON.parse(resp);
                    if (!actions || !actions.length) {
                        editor.windowManager.alert('No permission actions are defined for this page or page type.');
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
                if (checkPageDuplicate()) { errors++; }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
