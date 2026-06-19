<?php
namespace app\view\form;
use \lib\StdLib as lib;

// Abstract base for all stock-event entry pages (stocktake, delivery, transfer, adjustment).
// Subclasses must implement rendereventdefinition(), renderstocktableheader(), renderstockrow().
// Extends Form directly (not StdCRUDForm) because event pages are not standard CRUD pages.
abstract class StockEventForm extends \fw\view\form\Form {
    protected $trace       = false;
    protected $formname    = "stockeventform";
    protected $event_type        = "";   // set by subclass: 'stocktake','delivery','transfer','adjustment'
    protected $event_label       = "";   // set by subclass: human-readable title
    protected $event_icon        = "";   // set by subclass: HTML entity for banner icon
    protected $event_description = "";   // set by subclass: one-line description for banner

    protected $locations  = [];
    protected $suppliers  = [];
    protected $categories = [];
    protected $event      = [];    // in-progress event record from stock_event table, or []

    // Called by ViewController's prepare_stockevent_body().
    // $event: the current in-progress event record (empty array if none exists yet).
    public function init($session, array $locations = [], array $suppliers = [], array $categories = [], array $event = []) {
        parent::init($session);
        $this->locations  = $locations;
        $this->suppliers  = $suppliers;
        $this->categories = $categories;
        $this->event      = $event;
    }

    // =========================================================================
    // Abstract hooks for subclasses
    // =========================================================================

    // Returns the HTML for the event-definition section at the top of the page.
    // Subclass renders location / supplier / client dropdowns and a "Start" button.
    abstract protected function rendereventdefinition(): string;

    // Returns the <thead> inner HTML (one <tr>) for the stock table.
    abstract protected function renderstocktableheader(): string;

    // Returns one <tr> of the stock table body for the given stock row.
    // $row contains keys: stock_id, stock_name, category_name, movement_id, qty, stock_qoh, location_id.
    abstract protected function renderstockrow(array $row): string;

    // =========================================================================
    // AJAX entry point: renders all <tr> elements for the stock table body.
    // Called by RequestHandler for stockevent_getstock.
    // =========================================================================
    public function renderstocktable(array $rows): string {
        if (empty($rows)) {
            return '<tr><td colspan="99" class="se-empty">No stock items found.</td></tr>';
        }
        $html    = '';
        $lastcat = null;
        $n       = 0;
        foreach ($rows as $row) {
            $cat = $row['category_name'] ?? '';
            if ($cat !== $lastcat) {
                $html .= '<tr class="se-category-heading"><td colspan="99">'
                       . htmlspecialchars($cat ?: '(No category)')
                       . '</td></tr>';
                $lastcat = $cat;
                $n = 0;
            }
            $oddeven = ($n % 2 === 0) ? ' vols-row-odd' : ' vols-row-even';
            $html .= str_replace('class="se-stock-row"', 'class="se-stock-row' . $oddeven . '"', $this->renderstockrow($row));
            $n++;
        }
        return $html;
    }

    // =========================================================================
    // Shared HTML helpers
    // =========================================================================

    private function renderbanner(): string {
        if (!$this->event_description) return '';
        return '<div class="se-event-header se-event-header-' . htmlspecialchars($this->event_type) . '">'
             . '<span class="se-event-header-icon">' . $this->event_icon . '</span>'
             . '<span class="se-event-header-text">' . htmlspecialchars($this->event_description) . '</span>'
             . '</div>';
    }

    //

    // Builds a <select> of locations with a blank first option.
    protected function renderlocationselect(string $id, string $label, string $class = ''): string {
        $html  = '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        $html .= '<select id="' . $id . '" name="' . $id . '"' . ($class ? ' class="' . $class . '"' : '') . '>';
        $html .= '<option value="">-- Select --</option>';
        foreach ($this->locations as $loc) {
            $uncontrolled = !empty($loc['uncontrolled_issues']) ? ' data-uncontrolled="1"' : '';
            $html .= '<option value="' . (int)$loc['id'] . '"' . $uncontrolled . '>'
                   . htmlspecialchars($loc['name']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    // Builds a <select> of suppliers with a blank first option.
    protected function rendersupplierselect(string $id, string $label): string {
        $html  = '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        $html .= '<select id="' . $id . '" name="' . $id . '">';
        $html .= '<option value="">-- Select --</option>';
        foreach ($this->suppliers as $sup) {
            $html .= '<option value="' . (int)$sup['id'] . '">'
                   . htmlspecialchars($sup['name']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    protected function rendercategoryfilter(): string {
        $html  = '<div id="se-category-filter" class="se-event-def-row">';
        $html .= '<label for="se-category">Filter by category:</label>';
        $html .= '<select id="se-category">';
        $html .= '<option value="">All categories</option>';
        foreach ($this->categories as $cat) {
            $html .= '<option value="' . (int)$cat['id'] . '">'
                   . htmlspecialchars($cat['Name']) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        return $html;
    }

    private function renderdigitpad(): string {
        $html  = '<div id="se-digitpad" class="se-digitpad">';
        $html .= '<div id="se-action-buttons" class="se-action-buttons">';
        $html .= '<button type="button" id="se-close-btn"  class="vols-button" onclick="setTimeout(closestockevent,80)">Close ' . htmlspecialchars($this->event_label) . '</button>';
        $html .= '<button type="button" id="se-cancel-btn" class="vols-button vols-button-danger" onclick="setTimeout(cancelstockevent,80)">Cancel ' . htmlspecialchars($this->event_label) . '</button>';
        $html .= '</div>';
        $html .= '<div class="se-pad-display">';
        $html .= '<input type="text" id="se-pad-display" readonly tabindex="-1">';
        $html .= '</div>';
        $html .= '<div class="se-pad-keys">';
        foreach (['7','8','9','4','5','6','1','2','3'] as $k) {
            $html .= '<button type="button" class="se-digit-btn" data-key="' . $k . '" tabindex="-1">' . $k . '</button>';
        }
        $html .= '</div>';
        $html .= '<div class="se-pad-bottom">';
        $html .= '<button type="button" class="se-digit-btn se-digit-clear"   data-key="clear" tabindex="-1">CLR</button>';
        $html .= '<button type="button" class="se-digit-btn"                  data-key="0"     tabindex="-1">0</button>';
        $html .= '<button type="button" class="se-digit-btn se-digit-decimal" data-key="."     tabindex="-1">.</button>';
        $html .= '<button type="button" class="se-digit-btn se-digit-back"    data-key="back"  tabindex="-1">&#9003;</button>';
        $html .= '</div>';
        $html .= '<div class="se-pad-commit">';
        $html .= '<button type="button" class="se-digit-btn se-digit-add"      data-key="add"      tabindex="-1">+</button>';
        $html .= '<button type="button" class="se-digit-btn se-digit-replace"  data-key="replace"  tabindex="-1">REPLACE</button>';
        $html .= '<button type="button" class="se-digit-btn se-digit-subtract" data-key="subtract" tabindex="-1">-</button>';
        $html .= '</div>';
        $html .= '<div class="se-pad-nav">';
        $html .= '<button type="button" class="se-digit-btn se-digit-prev" data-key="prev" tabindex="-1">&#9166; Prev</button>';
        $html .= '<button type="button" class="se-digit-btn se-digit-next" data-key="next" tabindex="-1">Next &#9166;</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    // Returns a row containing the previous-events dropdown and the "New ..." button.
    // Subclasses call this at the end of rendereventdefinition().
    protected function renderpreviouseventsrow(): string {
        $html  = '<div id="se-prev-event-row" class="se-event-def-row" style="display:none">';
        $html .= '<label for="se-prev-event">Previous</label>';
        $html .= '<select id="se-prev-event"><option value="">-- None --</option></select>';
        $html .= '<button type="button" id="se-csv-btn" class="vols-button" style="display:none">Download CSV</button>';
        $html .= '<button type="button" id="se-start-btn" class="vols-button">New '
               . htmlspecialchars($this->event_label) . '</button>';
        $html .= '</div>';
        return $html;
    }

    // Override in subclass to inject extra fields into se-event-controls (e.g. total_weight).
    protected function renderextraeventfields(): string { return ''; }

    // The "event controls" section: category filter, stock table, digit pad, Close/Cancel buttons.
    // Hidden on initial page load when no event is in progress; shown by JS after event is created.
    private function rendereventcontrols(): string {
        $event_id = isset($this->event['id']) ? (int)$this->event['id'] : 0;
        $style    = $event_id ? '' : ' style="display:none"';
        $html     = '<div id="se-event-controls" class="se-event-controls"' . $style . '>';

        // Hidden state fields used by all AJAX calls.
        $html .= '<input type="hidden" id="se-event-id"    value="' . $event_id . '">';
        $html .= '<input type="hidden" id="se-event-type"  value="' . htmlspecialchars($this->event_type) . '">';
        $html .= '<input type="hidden" id="se-location-id" value="">';  // set by JS after event definition

        $html .= $this->renderextraeventfields();
        $html .= $this->rendercategoryfilter();

        $html .= '<div class="se-stock-and-pad">';
        $html .= '<div id="se-stock-table-container" class="se-stock-table-container">';
        $html .= '<table id="se-stock-table" class="se-stock-table">';
        $html .= '<thead>' . $this->renderstocktableheader() . '</thead>';
        $html .= '<tbody id="se-stock-table-body"></tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= $this->renderdigitpad();
        $html .= '</div>'; // se-stock-and-pad

        $html .= '</div>'; // se-event-controls
        return $html;
    }

    // =========================================================================
    // render() — called by StandardBody
    // =========================================================================
    public function render($pagenum = '', $nextpage = '', $subheading = "", $rights = [], $isadmin = false, $menu = "", $trace = false): string {
        if ($this->trace || $trace) { echo "Enter " . __METHOD__ . "<br>"; }

        $greeting = htmlspecialchars($this->session->getgreeting());
        $html  = '<div class="greetingcontainer">';
        $html .= '  <div class="greeting">Welcome ' . $greeting . '</div>';
        $html .= $menu;
        $html .= '</div>';
        $defaults = [];
        foreach ($this->locations as $loc) {
            if (!empty($loc['is_delivery_default']))      $defaults['delivery']      = (int)$loc['id'];
            if (!empty($loc['is_transfer_from_default'])) $defaults['transfer_from'] = (int)$loc['id'];
            if (!empty($loc['is_transfer_to_default']))   $defaults['transfer_to']   = (int)$loc['id'];
        }
        $html .= '<div class="vols-form-content se-event-page se-event-' . htmlspecialchars($this->event_type) . '"'
               . ' data-defaults="' . htmlspecialchars(json_encode($defaults)) . '"'
               . ' data-pagenum="' . intval($pagenum) . '"'
               . ' data-event-type="' . htmlspecialchars($this->event_type) . '">';
        $html .= '<h2 class="vols-form-pageheading">' . htmlspecialchars($this->event_label) . '</h2>';
        $html .= $this->renderbanner();
        $html .= $this->rendereventdefinition();
        $html .= $this->rendereventcontrols();
        $html .= '<script>' . $this->formscript() . '</script>';
        $html .= '</div>';

        if ($this->trace || $trace) { echo "Leave " . __METHOD__ . "<br>"; }
        return $html;
    }

    // =========================================================================
    // Shared JavaScript
    // =========================================================================
    public function formscript(): string {
        return <<<'JS'
var componentLog = {};

function formatprevdate(s) {
    var p = s.split(/[- :]/);
    var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return p[2]+' '+m[parseInt(p[1],10)-1]+' '+p[0]+' '+p[3]+':'+p[4];
}

function loadpreviousevents(event_type, loc1, loc2, sup) {
    doServerRequest(0, JSON.stringify({
        event_type:   event_type,
        location1_id: loc1 || '',
        location2_id: loc2 || '',
        supplier_id:  sup  || ''
    }), 'stockevent_getpreviousevents').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            var $sel = jQuery('#se-prev-event');
            $sel.html('<option value="">-- None --</option>');
            (r.events || []).forEach(function(ev) {
                $sel.append(jQuery('<option>').val(ev.id).text(formatprevdate(ev.date_closed))
                    .data('weight', ev.total_weight != null ? ev.total_weight : ''));
            });
        } catch(ex) { console.error('loadpreviousevents', ex, resp); }
    });
}

function setviewmode(on) {
    jQuery('#se-event-controls').toggleClass('se-readonly', on);
    jQuery('#se-digitpad').toggle(!on);
    jQuery('#se-action-buttons').toggle(!on);
}

// Checks whether any of the given location IDs has an in-progress stocktake.
// Calls callback() if clear; shows a dialog and does NOT call callback if blocked.
function checknostocktakeinprogress(locationIds, callback) {
    var locs      = locationIds.filter(Boolean);
    if (!locs.length) { callback(); return; }
    var remaining = locs.length;
    var blocked   = false;
    locs.forEach(function(locId) {
        getinprogressevent('stocktake', locId, null, null, function(r) {
            if (r.found) blocked = true;
            if (--remaining === 0) {
                if (blocked) {
                    jQuery.volsdialog('OKMSG',
                        'A stocktake is currently in progress at this location. Please close the stocktake before recording other transactions.',
                        function() { jQuery('.se-location-select').val(''); },
                        undefined, 'Stocktake in Progress');
                } else {
                    callback();
                }
            }
        });
    });
}

function getbreakdown(stockId) {
    var log = componentLog[String(stockId)];
    if (!log || log.length === 0) return null;
    return log.map(function(e, i) {
        return i === 0 ? String(e.val) : e.op + ' ' + e.val;
    }).join(' ');
}

(function() {
    var $activeInput = null;
    var saveTimer    = null;
    var audioCtx     = null;
    var keepAliveOsc = null;

    // Start an 18 kHz oscillator at -40 dB: above audible range but non-zero,
    // which keeps Chrome from auto-suspending the context between taps.
    function startKeepalive() {
        if (keepAliveOsc || !audioCtx || audioCtx.state !== 'running') return;
        try {
            var g = audioCtx.createGain();
            g.gain.value = 0.01;
            g.connect(audioCtx.destination);
            keepAliveOsc = audioCtx.createOscillator();
            keepAliveOsc.frequency.value = 18000;
            keepAliveOsc.connect(g);
            keepAliveOsc.start();
        } catch(e) {}
    }

    function unlockAudioCtx() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                audioCtx.onstatechange = function() {
                    if (audioCtx.state === 'running') startKeepalive();
                };
            }
            if (audioCtx.state !== 'running') {
                var buf = audioCtx.createBuffer(1, 1, audioCtx.sampleRate);
                var src = audioCtx.createBufferSource();
                src.buffer = buf; src.connect(audioCtx.destination); src.start(0);
                audioCtx.resume();
            } else {
                startKeepalive();
            }
        } catch(e) {}
    }

    function playtaptone() {
        try {
            if (!audioCtx) return;
            function playbeep() {
                var osc  = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                osc.connect(gain); gain.connect(audioCtx.destination);
                osc.type = 'sine'; osc.frequency.value = 1000;
                var t = audioCtx.currentTime;
                gain.gain.setValueAtTime(1.0, t);
                gain.gain.exponentialRampToValueAtTime(0.001, t + 0.05);
                osc.start(t); osc.stop(t + 0.05);
            }
            if (audioCtx.state === 'running') { playbeep(); }
            else { audioCtx.resume().then(playbeep); }
        } catch(e) {}
    }

    function schedulesave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function() {
            if ($activeInput) savemovement($activeInput);
        }, 1500);
    }

    // Track which qty input is active; unlock audio on focus (fires within gesture).
    jQuery(document).on('focus', '.se-qty', function() {
        $activeInput = jQuery(this);
        jQuery('#se-pad-display').val('');
        jQuery('#se-stock-table-body tr').css('background-color', '');
        jQuery(this).closest('tr').css('background-color', '#fff176');
        unlockAudioCtx();
    });

    // Row tap: unlock audio on touchstart (earliest possible gesture signal).
    jQuery(document).on('touchstart', '.se-stock-row', function() {
        unlockAudioCtx();
    }).on('click', '.se-stock-row', function(e) {
        if (!jQuery(e.target).is('input')) {
            jQuery(this).find('.se-qty').focus();
        }
    });

    // Desktop: mousedown fires before click, giving resume() a head start so
    // the context is running by the time playtaptone() is called.
    jQuery(document).on('mousedown', '.se-digit-btn, #se-close-btn, #se-cancel-btn', function() {
        unlockAudioCtx();
    });

    // Digit pad key press.
    // touchstart and click are separated to prevent double-firing on touch browsers
    // where click fires 300ms after touchstart even when preventDefault() was called.
    var lastPadTouch = 0;

    function handlepadkey(btn) {
        if (!$activeInput) return;
        var key    = jQuery(btn).data('key');
        var padVal = String(jQuery('#se-pad-display').val() || '');

        if (key === 'clear') {
            jQuery('#se-pad-display').val('');
        } else if (key === 'back') {
            jQuery('#se-pad-display').val(padVal.slice(0, -1));
        } else if (key === 'next' || key === 'prev') {
            var $inputs2 = jQuery('.se-qty:visible');
            var $target;
            if (key === 'next') {
                $target = $inputs2.eq($inputs2.index($activeInput[0]) + 1);
            } else {
                var pidx = $inputs2.index($activeInput[0]);
                $target  = pidx > 0 ? $inputs2.eq(pidx - 1) : jQuery();
            }
            var doNavigate = function() {
                clearTimeout(saveTimer);
                savemovement($activeInput);
                jQuery('#se-pad-display').val('');
                if ($target.length) { $target.focus(); } else { $activeInput.blur(); }
            };
            if (padVal !== '') {
                var $capturedInput = $activeInput;
                var capturedPadVal = padVal;
                jQuery('<div>').html('The pad shows <strong>' + capturedPadVal + '</strong> — apply it to this item first, or clear it?')
                    .dialog({
                        title: 'Pending Pad Value', modal: true, width: 440,
                        buttons: [
                            { text: 'Apply (+)', click: function() {
                                jQuery(this).dialog('close').remove();
                                var cur = parseFloat($capturedInput.val() || '0') || 0;
                                var num = parseFloat(capturedPadVal) || 0;
                                $capturedInput.val(String(Math.round((cur + num) * 10) / 10));
                                doNavigate();
                            }},
                            { text: 'CLR', click: function() {
                                jQuery(this).dialog('close').remove();
                                doNavigate();
                            }},
                            { text: 'Cancel', click: function() {
                                jQuery(this).dialog('close').remove();
                            }}
                        ]
                    });
                return;
            }
            doNavigate();
            return;
        } else if (key === 'add') {
            var current = parseFloat($activeInput.val() || '0') || 0;
            var padNum  = parseFloat(padVal || '0')  || 0;
            $activeInput.val(String(Math.round((current + padNum) * 10) / 10));
            jQuery('#se-pad-display').val('');
            if (padNum !== 0) {
                var sid = String($activeInput.data('stock-id'));
                if (!componentLog[sid]) componentLog[sid] = current !== 0 ? [{op: null, val: current}] : [];
                componentLog[sid].push(componentLog[sid].length === 0 ? {op: null, val: padNum} : {op: '+', val: padNum});
            }
            schedulesave();
        } else if (key === 'replace') {
            var repVal = padVal === '' ? '' : (parseFloat(padVal) || 0);
            $activeInput.val(repVal === '' ? '' : String(repVal));
            jQuery('#se-pad-display').val('');
            var sid = String($activeInput.data('stock-id'));
            componentLog[sid] = (repVal !== '' && repVal !== 0) ? [{op: null, val: repVal}] : [];
            schedulesave();
        } else if (key === 'subtract') {
            var current = parseFloat($activeInput.val() || '0') || 0;
            var padNum  = parseFloat(padVal || '0')  || 0;
            var result     = Math.round((current - padNum) * 10) / 10;
            var eventType  = jQuery('#se-event-type').val();
            if (result < 0 && eventType !== 'adjustment') {
                jQuery('#se-pad-display').val('');
                jQuery.volsdialog('OKMSG',
                    'Subtracting ' + padNum + ' from ' + current + ' would give a negative quantity — please recheck the amount.',
                    undefined, undefined, 'Cannot subtract');
            } else {
                $activeInput.val(String(result));
                jQuery('#se-pad-display').val('');
                if (padNum !== 0) {
                    var sid = String($activeInput.data('stock-id'));
                    if (!componentLog[sid]) componentLog[sid] = current !== 0 ? [{op: null, val: current}] : [];
                    componentLog[sid].push(componentLog[sid].length === 0 ? {op: null, val: result} : {op: '-', val: padNum});
                }
                schedulesave();
            }
        } else if (key === '.') {
            if (padVal.indexOf('.') === -1) {
                jQuery('#se-pad-display').val(padVal === '' ? '0.' : padVal + '.');
            }
        } else if (/^[0-9]$/.test(key)) {
            if (padVal.indexOf('.') !== -1) {
                var decParts = padVal.split('.');
                if (decParts[1].length < 1) {
                    jQuery('#se-pad-display').val(padVal + key);
                }
            } else {
                var raw = (padVal === '' || padVal === '0') ? key : padVal + key;
                jQuery('#se-pad-display').val(String(parseInt(raw, 10)));
            }
        }
    }

    jQuery(document).on('touchstart', '.se-digit-btn', function(e) {
        e.preventDefault();
        lastPadTouch = Date.now();
        unlockAudioCtx();
        playtaptone();
        handlepadkey(this);
    }).on('click', '.se-digit-btn', function() {
        if (Date.now() - lastPadTouch < 500) return;
        playtaptone();
        handlepadkey(this);
    });

    jQuery(document).on('touchstart', '#se-close-btn, #se-cancel-btn', function() {
        lastPadTouch = Date.now();
        unlockAudioCtx();
        playtaptone();
    }).on('click', '#se-close-btn, #se-cancel-btn', function() {
        if (Date.now() - lastPadTouch < 500) return;
        playtaptone();
    });

    // Immediate save when a qty field loses focus (cancels any pending debounce).
    jQuery(document).on('blur', '.se-qty', function() {
        clearTimeout(saveTimer);
        savemovement(jQuery(this));
    });

    // Tab / Shift+Tab move between qty inputs instead of into the digit pad.
    jQuery(document).on('keydown', '.se-qty', function(e) {
        if (e.key !== 'Tab') return;
        var $inputs = jQuery('.se-qty:visible');
        if (e.shiftKey) {
            var idx   = $inputs.index(this);
            var $prev = idx > 0 ? $inputs.eq(idx - 1) : jQuery();
            if ($prev.length) { e.preventDefault(); $prev.focus(); }
        } else {
            var $next = $inputs.eq($inputs.index(this) + 1);
            if ($next.length) { e.preventDefault(); $next.focus(); }
        }
    });

    // Reload stock table when category filter changes.
    jQuery(document).on('change', '#se-category', function() {
        var event_id = jQuery('#se-event-id').val();
        if (event_id && parseInt(event_id) > 0) {
            loadstock(event_id, jQuery(this).val(), '');
        }
    });

    // If an event is already in progress on page load, show controls and load stock.
    jQuery(function() {
        var event_id = parseInt(jQuery('#se-event-id').val() || '0');
        if (event_id > 0) {
            jQuery('#se-event-controls').show();
            loadstock(event_id, '');
        }

        // Recalculate table height whenever se-event-controls becomes visible,
        // and whenever the window is resized.
        var ctrl = document.getElementById('se-event-controls');
        if (ctrl) {
            new MutationObserver(function() {
                if (jQuery('#se-event-controls').is(':visible')) {
                    setTimeout(resizestocktable, 0);
                }
            }).observe(ctrl, { attributes: true, attributeFilter: ['style', 'class'] });
        }
        jQuery(window).on('resize', resizestocktable);
        if (jQuery('#se-event-controls').is(':visible')) {
            resizestocktable();
        }
    });

    // Keypad entry breakdown: show accumulated components for the active stock item.
    jQuery(document).on('click', '.se-log-btn', function(e) {
        e.stopPropagation();
        var $btn    = jQuery(this);
        var bd      = getbreakdown($btn.data('stock-id'));
        var message;
        if (bd) {
            message = bd;
        } else {
            var opening = $btn.closest('tr').find('.se-qty').val();
            message = (opening !== '' && parseFloat(opening) !== 0)
                ? 'Opening value: ' + opening + '. No changes this session.'
                : 'No breakdown recorded yet — use + and − to build up the count.';
        }
        jQuery.volsdialog('OKMSG', message, undefined, undefined, $btn.data('stock-name'));
    });

    // Previous event selected: load stock in read-only mode; deselect to clear.
    jQuery(document).on('change', '#se-prev-event', function() {
        var event_id = jQuery(this).val();
        if (!event_id) {
            jQuery('#se-event-controls').hide().removeClass('se-readonly');
            jQuery('#se-event-id').val('');
            jQuery('#se-csv-btn').hide();
            jQuery('#se-total-weight').val('').prop('readonly', false);
            setviewmode(false);
            return;
        }
        var weight = jQuery(this).find('option:selected').data('weight');
        jQuery('#se-total-weight').val(weight !== undefined ? weight : '').prop('readonly', true);
        jQuery('#se-event-id').val(event_id);
        jQuery('#se-event-controls').show();
        jQuery('#se-csv-btn').show();
        setviewmode(true);
        loadstock(event_id, '', '');
    });

    jQuery(document).on('click', '#se-csv-btn', function() {
        var event_id = jQuery('#se-event-id').val();
        if (!event_id) return;
        doServerRequest(0, JSON.stringify({ event_id: event_id }), 'stockevent_exportcsv').then(function(resp) {
            try {
                var r = JSON.parse(resp);
                if (!r.success) { jQuery.volsdialog('OKMSG', r.error || 'Export failed.', undefined, undefined, 'CSV Export'); return; }
                var blob = new Blob(['﻿' + r.csv], { type: 'text/csv;charset=utf-8' });
                var url  = URL.createObjectURL(blob);
                var a    = document.createElement('a');
                a.href     = url;
                a.download = r.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch(ex) { console.error('exportcsv', ex, resp); }
        });
    });

    // Save the active input when the user leaves the page (back button, menu nav, tab switch).
    window.addEventListener('pagehide', function() {
        if ($activeInput) {
            clearTimeout(saveTimer);
            savemovement($activeInput);
        }
    });
})();

function savemovement($input) {
    if (jQuery('#se-event-controls').hasClass('se-readonly')) return;
    var stock_id    = $input.data('stock-id');
    var movement_id = $input.data('movement-id') || 0;
    var value       = $input.val();
    var event_id    = jQuery('#se-event-id').val();
    var location_id = jQuery('#se-location-id').val();
    var event_type  = jQuery('#se-event-type').val();
    if (!stock_id || !event_id) return;
    if (value !== '' && parseFloat(value) < 0 && event_type !== 'adjustment') { $input.val('0'); value = '0'; }
    if (value === '' && !parseInt(movement_id)) return;
    doServerRequest(0, JSON.stringify({
        stock_id:    stock_id,
        movement_id: movement_id,
        value:       value,
        event_id:    event_id,
        location_id: location_id,
        event_type:  event_type
    }), 'stockevent_savemovement').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) {
                $input.data('movement-id', r.movement_id);
                var $row = $input.closest('tr');
                $row.addClass('se-saved');
                setTimeout(function() { $row.removeClass('se-saved'); }, 1000);
            } else {
                jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Error saving');
            }
        } catch(ex) { console.error('savemovement parse error', ex, resp); }
    });
}

function resizestocktable() {
    var $container = jQuery('#se-stock-table-container');
    if (!$container.length || !$container.is(':visible')) return;
    var containerTop = $container[0].getBoundingClientRect().top;
    var $footer = jQuery('#footercontainer');
    var footerHeight = $footer.length ? $footer.outerHeight() : 0;
    var available = Math.max(200, window.innerHeight - containerTop - footerHeight - 4);
    $container.css('height', available + 'px');
}

function loadstock(event_id, category_id, supplier_id) {
    var event_type = jQuery('#se-event-type').val();
    jQuery('#se-stock-table-body').html('<tr><td colspan="99" class="se-loading">Loading\u2026</td></tr>');
    doServerRequest(0, JSON.stringify({
        event_id:    event_id,
        category_id: category_id || '',
        supplier_id: supplier_id || '',
        event_type:  event_type
    }), 'stockevent_getstock').then(function(resp) {
        jQuery('#se-stock-table-body').html(resp);
        jQuery('#se-stock-table-body .se-qty').prop('readonly', true);
        resizestocktable();
    });
}

function reloadcurrentpage() {
    var pn = jQuery('.se-event-page').data('pagenum');
    if (pn) {
        jQuery('#menuactionform input[name="p"]').val(pn);
        jQuery('#menuactionform input[name="pp"]').val('');
        jQuery('#menuactionform').trigger('submit');
    } else {
        location.reload();
    }
}

function closestockevent() {
    jQuery.volsdialog('YESNO', 'Close this event? This will finalise all entries.',
        function() {
            var event_id = jQuery('#se-event-id').val();
            doServerRequest(0, JSON.stringify({ event_id: event_id }), 'stockevent_closeevent').then(function(resp) {
                try {
                    var r = JSON.parse(resp);
                    if (r.success) { reloadcurrentpage(); }
                    else { jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Cannot close event'); }
                } catch(ex) { console.error(ex, resp); }
            });
        },
        undefined, 'Close Event?'
    );
}

function cancelstockevent() {
    jQuery.volsdialog('YESNO', 'Cancel this event? All entries will be deleted.',
        function() {
            var event_id = jQuery('#se-event-id').val();
            doServerRequest(0, JSON.stringify({ event_id: event_id }), 'stockevent_cancelevent').then(function(resp) {
                try {
                    var r = JSON.parse(resp);
                    if (r.success) { reloadcurrentpage(); }
                    else { jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Cannot cancel event'); }
                } catch(ex) { console.error(ex, resp); }
            });
        },
        undefined, 'Cancel Event?'
    );
}

function createstockevent(event_type, location1_id, location2_id, supplier_id, stock_client_id, onSuccess) {
    doServerRequest(0, JSON.stringify({
        event_type:      event_type,
        location1_id:    location1_id    || '',
        location2_id:    location2_id    || '',
        supplier_id:     supplier_id     || '',
        stock_client_id: stock_client_id || ''
    }), 'stockevent_createevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) {
                jQuery('#se-event-id').val(r.event_id);
                jQuery('#se-event-controls').show();
                if (typeof onSuccess === 'function') onSuccess(r.event_id);
            } else {
                jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Cannot start event');
            }
        } catch(ex) { console.error(ex, resp); }
    });
}

function getinprogressevent(event_type, location1_id, location2_id, supplier_id, onResult) {
    doServerRequest(0, JSON.stringify({
        event_type:   event_type,
        location1_id: location1_id || '',
        location2_id: location2_id || '',
        supplier_id:  supplier_id  || ''
    }), 'stockevent_getinprogressevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (typeof onResult === 'function') onResult(r);
        } catch(ex) { console.error(ex, resp); }
    });
}
JS;
    }
}
