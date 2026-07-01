<?php
namespace apptable;
use \lib\StdLib as lib;
class StockLocationTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"                        => "",
            "name"                      => "",
            "uncontrolled_issues"       => "",
            "is_delivery_default"       => "0",
            "is_transfer_from_default"  => "0",
            "is_transfer_to_default"    => "0",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    public function setdefault(string $type, int $location_id, string &$errormessage): bool {
        $col_map = [
            'delivery'      => 'is_delivery_default',
            'transfer_from' => 'is_transfer_from_default',
            'transfer_to'   => 'is_transfer_to_default',
        ];
        if (!isset($col_map[$type])) {
            $errormessage = 'Invalid default type';
            return false;
        }
        $col     = $col_map[$type];
        $numrows = 0;
        if (!$this->update("{$col} = 0", '', $numrows, $errormessage)) {
            return false;
        }
        if ($location_id > 0) {
            $result = false;
            if (!$this->execute_params("UPDATE stock_location SET {$col} = 1 WHERE id = ?", [$location_id], $result, $numrows, $errormessage)) {
                return false;
            }
        }
        return true;
    }
}
