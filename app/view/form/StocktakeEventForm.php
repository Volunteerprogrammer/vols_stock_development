<?php
namespace app\view\form;
use \lib\StdLib as lib;

class StocktakeEventForm extends StockEventForm {
    protected $trace             = false;
    protected $formname          = "stocktakeeventform";
    protected $event_type        = "stocktake";
    protected $event_label       = "Stocktake";
    protected $event_icon        = "&#9998;";
    protected $event_description = "Select a location and count the stock items on hand. Leave any item blank to skip it.";

    protected function rendereventdefinition(): string {
        $html  = '<div id="se-event-def" class="se-event-def">';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->renderlocationselect('se-location1', 'Location', 'se-location-select');
        $html .= '</div>';
        $html .= $this->renderpreviouseventsrow();
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-expected se-readonly-only">Expected</th>'
             . '<th class="se-th-qty">Count</th>'
             . '<th class="se-th-variance se-readonly-only">Variance</th>'
             . '</tr>';
    }

    // $row keys: stock_id, stock_name, category_name, movement_id, qty, stock_qoh, location_id
    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        // stock_qoh is the actual count recorded; null means not yet counted → show empty input
        $value       = ($row['stock_qoh'] !== null && $row['stock_qoh'] !== '') ? (int)$row['stock_qoh'] : '';

        $expected = isset($row['calculated_qoh']) ? (int)$row['calculated_qoh'] : '';
        $variance = ($row['qty'] !== null && $row['qty'] !== '') ? (int)$row['qty'] : null;
        $var_class = $variance === null ? '' : ($variance > 0 ? ' se-variance-pos' : ($variance < 0 ? ' se-variance-neg' : ''));
        $var_str   = $variance === null ? '&mdash;' : ($variance > 0 ? '+' . $variance : (string)$variance);
        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '"'
             . ($expected !== '' ? ' data-expected="' . $expected . '"' : '') . '>'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-expected se-readonly-only">' . ($expected !== '' ? $expected : '&mdash;') . '</td>'
             . '<td class="se-td-qty">'
             . '<div class="se-qty-wrap">'
             . '<input type="number" min="0" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric">'
             . '<button type="button" class="se-log-btn" data-stock-id="' . $stock_id . '" data-stock-name="' . $stock_name . '" tabindex="-1" title="Show count breakdown">?</button>'
             . '</div>'
             . '</td>'
             . '<td class="se-td-variance se-readonly-only' . $var_class . '">' . $var_str . '</td>'
             . '</tr>';
    }

    // Appends stocktake-specific JS to the shared base script.
    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- StocktakeEventForm-specific JS ----

// Warn when the count entered exceeds the system's expected QOH for that item.
// Only fires when the user explicitly commits via +, REPLACE, or - buttons.
(function() {
    var _alertArmed = false;

    jQuery(document).on('touchstart click', '.se-digit-add, .se-digit-replace, .se-digit-subtract', function() {
        _alertArmed = true;
    });

    var _baseSave = savemovement;
    savemovement = function($input) {
        var doAlert  = _alertArmed;
        _alertArmed  = false;
        var counted  = parseFloat($input.val());
        var $row     = $input.closest('tr');
        var expected = $row.data('expected');
        _baseSave($input);
        if (doAlert && !isNaN(counted) && expected !== undefined && counted > expected) {
            var name = $row.find('.se-td-name').text();
            setTimeout(function() {
                jQuery.volsdialog('OKMSG',
                    'Count of ' + counted + ' is higher than expected (' + expected + ').',
                    undefined, undefined, name);
            }, 100);
        }
    };
})();

// Override the base closestockevent() so that for uncontrolled-issues locations
// the operator is asked whether this is an end-of-session stocktake before the
// variance issues event is created.
function closestockevent() {
    var event_id       = jQuery('#se-event-id').val();
    var isUncontrolled = jQuery('#se-location1 option:selected').data('uncontrolled') == 1;

    function doclose(createIssue) {
        doServerRequest(0, JSON.stringify({ event_id: event_id, create_issue: createIssue }), 'stockevent_closeevent').then(function(resp) {
            try {
                var r = JSON.parse(resp);
                if (r.success) {
                    if (r.warning) {
                        jQuery.volsdialog('OKMSG', r.warning, function() { reloadcurrentpage(); }, undefined, 'Stocktake Closed — Note');
                    } else {
                        reloadcurrentpage();
                    }
                } else {
                    jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Cannot close stocktake');
                }
            } catch(ex) { console.error(ex, resp); }
        });
    }

    jQuery.volsdialog('YESNO', 'Close this stocktake? This will finalise all entries.',
        function() {
            if (!isUncontrolled) { doclose(1); return; }
            jQuery('<div>')
                .html('<p>Is this an <strong>End of Foodbank Session</strong> stocktake?</p>')
                .dialog({
                    title: 'End of Session?', modal: true, width: 500,
                    buttons: {
                        'Yes': function() { jQuery(this).dialog('close'); doclose(1); },
                        'No':  function() { jQuery(this).dialog('close'); doclose(0); }
                    }
                });
        },
        undefined, 'Close Stocktake?'
    );
}

function resumestocktake(event_id, location_id, location_name) {
    jQuery('#se-location1').val(location_id);
    jQuery('#se-event-id').val(event_id);
    jQuery('#se-location-id').val(location_id);
    jQuery('#se-prev-event-row').hide();
    jQuery('#se-prev-event').val('');
    setviewmode(false);
    jQuery('#se-event-controls').show();
    loadstock(event_id, '');
}

jQuery(function() {
    // On page load, if exactly one stocktake is in progress globally, auto-resume it.
    // With multiple locations allowed to run simultaneously, only auto-select when unambiguous.
    doServerRequest(0, JSON.stringify({}), 'stockevent_getanyinprogressstocktake').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.count === 1 && r.event && r.event.id) {
                resumestocktake(r.event.id, r.event.location1_id, r.event.location1_name);
            }
        } catch(ex) { console.error('getanyinprogressstocktake parse error', ex, resp); }
    });

    // Location dropdown change: check for in-progress stocktake at that location.
    jQuery(document).on('change', '#se-location1', function() {
        var loc = jQuery(this).val();
        jQuery('#se-event-controls').hide().removeClass('se-readonly');
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        jQuery('#se-prev-event-row').hide();
        jQuery('#se-prev-event').val('');
        setviewmode(false);
        if (!loc) return;

        getinprogressevent('stocktake', loc, null, null, function(r) {
            if (r.found && r.event && r.event.id) {
                resumestocktake(r.event.id, loc, '');
            } else {
                jQuery('#se-prev-event-row').show();
                loadpreviousevents('stocktake', loc, null, null);
            }
        });
    });

    jQuery(document).on('click', '#se-start-btn', function() {
        var loc = jQuery('#se-location1').val();
        if (!loc) { jQuery.volsdialog('OKMSG', 'Please select a location first.', undefined, undefined, 'Select Location'); return; }
        jQuery('#se-prev-event-row').hide();
        jQuery('#se-prev-event').val('');
        setviewmode(false);
        createstockevent('stocktake', loc, null, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc);
            loadstock(event_id, '');
        });
    });
});
JS;
        return $base . $extra;
    }
}
