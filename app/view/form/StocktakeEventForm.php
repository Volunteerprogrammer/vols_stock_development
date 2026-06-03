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
        $html .= '<button type="button" id="se-start-btn" class="vols-button" style="display:none">Start Stocktake</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-qty">Count</th>'
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

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '">'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-qty">'
             . '<input type="number" min="0" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric">'
             . '</td>'
             . '</tr>';
    }

    // Appends stocktake-specific JS to the shared base script.
    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- StocktakeEventForm-specific JS ----

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
                if (r.success) { location.reload(); }
                else { jQuery.volsdialog('OKMSG', r.error, undefined, undefined, 'Cannot close stocktake'); }
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
    jQuery('#se-start-btn').hide();
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
    jQuery('#se-location1').on('change', function() {
        var loc = jQuery(this).val();
        jQuery('#se-start-btn').hide();
        jQuery('#se-event-controls').hide();
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        if (!loc) return;

        getinprogressevent('stocktake', loc, null, null, function(r) {
            if (r.found && r.event && r.event.id) {
                resumestocktake(r.event.id, loc, '');
            } else {
                jQuery('#se-start-btn').show();
            }
        });
    });

    jQuery('#se-start-btn').on('click', function() {
        var loc = jQuery('#se-location1').val();
        if (!loc) { jQuery.volsdialog('OKMSG', 'Please select a location first.', undefined, undefined, 'Select Location'); return; }
        jQuery('#se-start-btn').hide();
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
