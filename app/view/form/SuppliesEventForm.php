<?php
namespace app\view\form;
use \lib\StdLib as lib;

// "Supplies" page: records stock issued out of a location (event type 'issue').
// Modelled on AdjustmentEventForm, but quantities are positive only and reduce QOH.
// Issue events were previously created only by the system (stocktake variance at
// uncontrolled-issues locations); those also appear in this page's previous-events list.
class SuppliesEventForm extends StockEventForm {
    protected $trace             = false;
    protected $formname          = "supplieseventform";
    protected $event_type        = "issue";
    protected $event_label       = "Supply";
    protected $event_icon        = "&#128228;";
    protected $event_description = "Use this page to record stock issued to clients or other recipients";

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
             . '<th class="se-th-countby">Count By</th>'
             . '<th class="se-th-qty">Qty Supplied</th>'
             . '</tr>';
    }

    // Supplied qty is stored positive; issue events subtract it from QOH.
    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $count_by    = htmlspecialchars($row['count_by']      ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        $value       = ($row['qty'] !== null && $row['qty'] !== '' && $row['qty'] != 0) ? (int)$row['qty'] : '';

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '">'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-countby">'  . $count_by   . '</td>'
             . '<td class="se-td-qty">'
             . '<div class="se-qty-wrap">'
             . '<input type="number" min="0" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric">'
             . '<button type="button" class="se-log-btn" data-stock-id="' . $stock_id . '" data-stock-name="' . $stock_name . '" tabindex="-1" title="Show entry breakdown">?</button>'
             . '</div>'
             . '</td>'
             . '</tr>';
    }

    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- SuppliesEventForm-specific JS ----
jQuery(function() {
    jQuery(document).on('change', '#se-location1', function() {
        var loc = jQuery(this).val();
        jQuery('#se-event-controls').hide().removeClass('se-readonly');
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        jQuery('#se-prev-event-row').hide();
        jQuery('#se-prev-event').val('');
        setviewmode(false);
        if (!loc) return;

        checknostocktakeinprogress([loc], function() {
            getinprogressevent('issue', loc, null, null, function(r) {
                if (r.found && r.event && r.event.id) {
                    jQuery('#se-event-id').val(r.event.id);
                    jQuery('#se-location-id').val(loc);
                    jQuery('#se-event-controls').show();
                    loadstock(r.event.id, '');
                } else {
                    jQuery('#se-prev-event-row').show();
                    loadpreviousevents('issue', loc, null, null);
                }
            });
        });
    });

    jQuery(document).on('click', '#se-start-btn', function() {
        var loc = jQuery('#se-location1').val();
        if (!loc) { jQuery.volsdialog('OKMSG', 'Please select a location first.', undefined, undefined, 'Select Location'); return; }
        jQuery('#se-prev-event-row').hide();
        jQuery('#se-prev-event').val('');
        setviewmode(false);
        createstockevent('issue', loc, null, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc);
            loadstock(event_id, '');
        });
    });
});
JS;
        return $base . $extra;
    }
}
