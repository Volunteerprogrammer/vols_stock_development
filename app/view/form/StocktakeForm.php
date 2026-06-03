<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StocktakeForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stocktakeform";
    protected $objname     = "Stocktake";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
        // Remove all standard CRUD action buttons — this form uses its own submit button
        $this->actionbuttons = [];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = array(
            "id"           => "",
            "Name"         => "",
            "Code"         => "",
            "category_id"  => "",
            "category_name"=> "",
            "current_qty"  => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stocktake-header">';
        $formfields .= '<span class="vols-stocktake-icon">&#9998;</span>';
        $formfields .= '<span class="vols-stocktake-headertext">Record a stocktake. Enter the counted quantity for each item. Leave blank to skip an item.</span>';
        $formfields .= '</div>';

        $currentcategory = null;
        foreach ($this->alldata as $item) {
            $cat = htmlspecialchars($item["category_name"] ?? "Uncategorised");
            if ($cat !== $currentcategory) {
                $currentcategory = $cat;
                $formfields .= '<div class="vols-stocktake-category-heading">'.$cat.'</div>';
            }
            $id       = (int)$item["id"];
            $name     = htmlspecialchars($item["Name"]);
            $code     = htmlspecialchars($item["Code"]);
            $current  = htmlspecialchars($item["current_qty"] ?? 0);

            $formfields .= '<div class="vols-tablerow vols-stocktake-row" id="stockrow_'.$id.'">';
            $formfields .= '  <div class="vols-tablecell vols-stocktake-name">'.$name.' <span class="vols-stocktake-code">('.$code.')</span></div>';
            $formfields .= '  <div class="vols-tablecell vols-stocktake-current">Current: <strong>'.$current.'</strong></div>';
            $formfields .= '  <div class="vols-tablecell vols-stocktake-input">';
            $formfields .= '    <label class="vols-stocktake-label">New Count:</label>';
            $formfields .= '    <input type="number" name="qty_'.$id.'" id="qty_'.$id.'" class="vols-form-input vols-stocktake-qty" min="0" step="1" placeholder="" />';
            $formfields .= '  </div>';
            $formfields .= '  <div class="vols-tablecell vols-stocktake-input">';
            $formfields .= '    <label class="vols-stocktake-label">Unit:</label>';
            $formfields .= '    <input type="text" name="unit_'.$id.'" id="unit_'.$id.'" class="vols-form-input vols-stocktake-unit" maxlength="24" placeholder="e.g. kg, can" />';
            $formfields .= '  </div>';
            $formfields .= '</div>';
        }

        $formfields .= '<div class="vols-tablerow vols-stocktake-submitrow">';
        $formfields .= '  <div class="vols-tablecell">';
        $formfields .= '    <div id="savestocktake" class="clickable action doitbg">Save Stocktake</div>';
        $formfields .= '  </div>';
        $formfields .= '</div>';

        // noselection = true removes the dropdown; noactionrow = true removes action buttons
        $this->preparecommontop(true, true, '', '');
        return $formfields;
    }

    public function formscript() {
        $formname = $this->formname;
        $script  = "jQuery(function () {\n";
        $script .= "    jQuery('#savestocktake').on('click', function() {\n";
        $script .= "        var hasEntry = false;\n";
        $script .= "        jQuery('.vols-stocktake-qty').each(function() {\n";
        $script .= "            if (jQuery(this).val() !== '') { hasEntry = true; }\n";
        $script .= "        });\n";
        $script .= "        if (!hasEntry) {\n";
        $script .= "            jQuery.volsdialog('OKMSG', 'Please enter at least one stock count before saving.', undefined, undefined, 'Validation');\n";
        $script .= "            return false;\n";
        $script .= "        }\n";
        $script .= "        jQuery('#action').val('stocktake');\n";
        $script .= "        jQuery('#hiddenid').val('0');\n";
        $script .= "        jQuery('#{$formname}').trigger('submit');\n";
        $script .= "    });\n";
        $script .= "});\n";
        $script .= "function formhaserrors() { return 0; }\n";
        $script .= "function displayselectedrecord() {}\n";
        return $script;
    }
}
