<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockform";
    protected $objname     = "Stock Item";
    protected $stockid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
        $this->stockid = $this->requestdata["id"] ?? "";
    }

    public function initfields() {
        $this->fields = array(
            "id"          => "",
            "Name"        => "",
            "Code"        => "",
            "category_id" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $categorydata = array_combine(
            array_column($this->parents['categories'] ?? $this->parents, "id"),
            array_column($this->parents['categories'] ?? $this->parents, "Name")
        );
        $optn = [];
        $formfields  = '<div class="vols-stockmaint-header vols-stockitem-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128230;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock items. Add, edit or delete items that can be tracked in inventory.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("Name", 1, "", 'Name', '', 20, 64, true, '', '');
        $formfields .= $this->component->buildinputrow("Code", 2, "", 'Code', 'Short code for this item', 20, 20, true, '', '');
        $formfields .= $this->component->buildselectrow("category_id", 3, 1, 'Category', $categorydata, "", $optn, false, false, true, false, '', false);

        $alllocations = $this->parents['locations'] ?? [];
        if (!empty($alllocations)) {
            $formfields .= $this->component->rendersectionheading(
                "Stock levels by location",
                subheading: "Set target qty, minimum qty, and stocktake position for each location. Position orders items within their category during a stocktake — items without a position follow alphabetically.",
                inputgroup: "stocklevelsloc"
            );
            $this->component->setwidths(30, 65, 5);
            $n = count($alllocations);
            foreach ($alllocations as $i => $loc) {
                $tgt = $this->component->rendertextinput("target_qty_" . $loc['id'], 5, 5, '', false, '', '', '', 4 + $i,           false, false, false, 1, '', '');
                $min = $this->component->rendertextinput("min_qty_"    . $loc['id'], 5, 5, '', false, '', '', '', 4 + $n + $i,       false, false, false, 1, '', '');
                $pos = $this->component->rendertextinput("stkpos_"     . $loc['id'], 5, 5, '', false, '', '', '', 4 + 2 * $n + $i,   false, false, false, 1, '', '');
                $locfields = 'target &nbsp;' . $tgt . ' &nbsp;&nbsp; min &nbsp;' . $min . ' &nbsp;&nbsp; position &nbsp;' . $pos;
                $formfields .= $this->component->renderformrow(
                    "loc_row_" . $loc['id'], "", $loc['name'], false,
                    "", "", "", $locfields
                );
            }
            $this->component->setwidths(30, 40, 30);
        }

        $alllocations = $this->parents['locations'] ?? [];
        $formfields  .= $this->component->rendersectionheading("Stock Movements", inputgroup: "stockmovements");
        $formfields  .= '<div class="childcontainer stockmovements grouped">';
        $formfields  .= '<div class="vols-stockmovements-filter">';
        $formfields  .= '<label class="vols-stockmovements-filter-label" for="mov_location_id">Location:</label>';
        $formfields  .= '<select id="mov_location_id" class="vols-stockmovements-locselect" onchange="filterStockMovements(this.value)">';
        $formfields  .= '<option value="">All locations</option>';
        foreach ($alllocations as $loc) {
            $formfields .= '<option value="' . (int)$loc['id'] . '">' . htmlspecialchars($loc['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';
        $formfields .= '<div id="stock-movements-container"></div>';
        $formfields .= '</div>'; // childcontainer stockmovements

        $this->preparecommontop(false, false, '', $this->stockid);
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
            'loadStockMovements(selectedid);',  // postloadfieldsscript
            'clearStockMovements();',           // postclearfieldsscript
            false,  // trace
            '',     // multisubmit
            ''      // presavescript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#Name").val()) {
                    jQuery("#Namerow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#Code").val()) {
                    jQuery("#Coderow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#category_id").val()) {
                    jQuery("#category_idrow_error").html("(Please select a category.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
            (function() {
                var _orig = disableallinputstatus;
                disableallinputstatus = function(disabled) {
                    _orig(disabled);
                    jQuery('#mov_location_id').prop('disabled', false);
                };
            })();
            function loadStockMovements(stockId) {
                if (!stockId || stockId == 0) { clearStockMovements(); return; }
                jQuery('#stock-movements-container').html('<div class="vols-stockmovements-empty">Loading…</div>');
                doServerRequest(stockId, '{}', 'stock_getmovements').then(function(response) {
                    try {
                        renderMovementsTable(JSON.parse(response));
                    } catch(e) {
                        jQuery('#stock-movements-container').html('<div class="vols-stockmovements-empty">Could not load movements.</div>');
                    }
                });
            }
            function clearStockMovements() {
                jQuery('#stock-movements-container').html('');
                jQuery('#mov_location_id').val('');
            }
            function renderMovementsTable(movements) {
                if (!movements || movements.length === 0) {
                    jQuery('#stock-movements-container').html('<div class="vols-stockmovements-empty">No movement history for this item.</div>');
                    return;
                }
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

                // Compute signed change and running QOH per location (oldest-first pass).
                var runningQoh = {};
                var enhanced = movements.slice().reverse();
                for (var i = 0; i < enhanced.length; i++) {
                    var m = enhanced[i];
                    var loc   = m.location_id || 0;
                    var qty   = parseFloat(m.qty);
                    var sqoh  = (m.stock_qoh !== null && m.stock_qoh !== undefined && m.stock_qoh !== '')
                                ? parseFloat(m.stock_qoh) : null;
                    var change, newQoh;
                    if (m.event === 'stocktake') {
                        // Resets QOH to the counted value; change = variance stored in qty.
                        change = isNaN(qty) ? null : qty;
                        newQoh = (sqoh !== null && !isNaN(sqoh)) ? sqoh : null;
                        if (newQoh !== null) runningQoh[loc] = newQoh;
                    } else if (m.event === 'issue') {
                        // qty stored positive; issues reduce QOH.
                        change = isNaN(qty) ? null : -qty;
                        newQoh = (runningQoh[loc] !== undefined ? runningQoh[loc] : 0) + (change || 0);
                        runningQoh[loc] = newQoh;
                    } else {
                        // delivery, transfer, adjustment — qty already carries the correct sign.
                        change = isNaN(qty) ? null : qty;
                        newQoh = (runningQoh[loc] !== undefined ? runningQoh[loc] : 0) + (change || 0);
                        runningQoh[loc] = newQoh;
                    }
                    enhanced[i] = Object.assign({}, m, { _change: change, _new_qoh: newQoh });
                }
                enhanced.reverse(); // back to newest-first for display

                var html = '<div class="vols-stockmovements-table">'
                         + '<div class="vols-stockmovements-colheadings">'
                         + '<div class="vols-stockmovements-col-date">Date / Time</div>'
                         + '<div class="vols-stockmovements-col-type">Type</div>'
                         + '<div class="vols-stockmovements-col-loc">Location</div>'
                         + '<div class="vols-stockmovements-col-change">Change</div>'
                         + '<div class="vols-stockmovements-col-qoh">QOH</div>'
                         + '</div>';

                for (var i = 0; i < enhanced.length; i++) {
                    var m = enhanced[i];
                    var d = new Date(m.event_date.replace(' ', 'T'));
                    var hh = String(d.getHours()).padStart(2, '0');
                    var mm = String(d.getMinutes()).padStart(2, '0');
                    var dateStr = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ' ' + hh + ':' + mm;
                    var label = m.event.charAt(0).toUpperCase() + m.event.slice(1);
                    var loc   = m.location_name || '—';

                    var ch = m._change;
                    var changeStr, changeClass;
                    if (ch === null || isNaN(ch)) {
                        changeStr = '—'; changeClass = '';
                    } else {
                        changeStr  = (ch > 0 ? '+' : '') + (ch % 1 === 0 ? ch.toString() : ch.toFixed(1));
                        changeClass = ch > 0 ? ' vols-variance-pos' : (ch < 0 ? ' vols-variance-neg' : '');
                    }

                    var qohVal = m._new_qoh;
                    var qohStr = (qohVal !== null && qohVal !== undefined && !isNaN(qohVal))
                        ? (qohVal % 1 === 0 ? qohVal.toString() : qohVal.toFixed(1)) : '—';

                    html += '<div class="vols-stockmovements-row" data-loc-id="' + (m.location_id || 0) + '">'
                          + '<div class="vols-stockmovements-col-date">' + dateStr + '</div>'
                          + '<div class="vols-stockmovements-col-type"><span class="vols-stockmov-' + m.event + '">' + label + '</span></div>'
                          + '<div class="vols-stockmovements-col-loc">' + loc + '</div>'
                          + '<div class="vols-stockmovements-col-change' + changeClass + '">' + changeStr + '</div>'
                          + '<div class="vols-stockmovements-col-qoh">' + qohStr + '</div>'
                          + '</div>';
                }
                html += '</div>';
                jQuery('#stock-movements-container').html(html);
                var currentLoc = jQuery('#mov_location_id').val();
                if (currentLoc) { filterStockMovements(currentLoc); }
            }
            function filterStockMovements(locId) {
                jQuery('.vols-stockmovements-row').each(function() {
                    jQuery(this).toggle(!locId || String(jQuery(this).data('loc-id')) === String(locId));
                });
            }
        JS;
        return $script;
    }
}
