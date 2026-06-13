<?php
namespace app\view\form;
use \lib\StdLib as lib;

class AdjustmentEventForm extends StockEventForm {
    protected $trace       = false;
    protected $formname    = "adjustmenteventform";
    protected $event_type  = "adjustment";
    protected $event_label       = "Stock Adjustment";
    protected $event_icon        = "&#177;";
    protected $event_description = "Select a location, then enter adjustments (positive to add, negative to remove).";

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
             . '<th class="se-th-qty">Adjustment</th>'
             . '</tr>';
    }

    // Adjustment qty can be positive (add) or negative (remove).
    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        $value       = ($row['qty'] !== null && $row['qty'] !== '' && $row['qty'] != 0) ? (int)$row['qty'] : '';

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '">'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-qty">'
             . '<input type="number" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric"'
             . ' title="Enter positive to add, negative to remove">'
             . '</td>'
             . '</tr>';
    }

    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- AdjustmentEventForm-specific JS ----
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
            getinprogressevent('adjustment', loc, null, null, function(r) {
                if (r.found && r.event && r.event.id) {
                    jQuery('#se-event-id').val(r.event.id);
                    jQuery('#se-location-id').val(loc);
                    jQuery('#se-event-controls').show();
                    loadstock(r.event.id, '');
                } else {
                    jQuery('#se-prev-event-row').show();
                    loadpreviousevents('adjustment', loc, null, null);
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
        createstockevent('adjustment', loc, null, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc);
            loadstock(event_id, '');
        });
    });
});
JS;
        return $base . $extra;
    }
}
