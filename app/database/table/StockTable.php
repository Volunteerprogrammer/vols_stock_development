<?php
namespace apptable;
use \lib\StdLib as lib;
class StockTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"          => "",
            "Name"        => "",
            "Code"        => "",
            "category_id" => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    public function getstockwithlevels(&$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        // All movements after the last stocktake are identified by movement_date
        // rather than id, so that the calculation is correct even if records
        // are inserted out of order.
        $last_st  = "SELECT MAX(sm_st.movement_date) FROM stock_movement sm_st"
                  . " WHERE sm_st.stock_id = s.id AND sm_st.movement_type = 'stocktake_adjustment'";
        $query  = "SELECT s.id, s.Name, s.Code, s.category_id, sc.Name as category_name,";
        // Last stocktake date
        $query .= " (SELECT sm1.movement_date FROM stock_movement sm1";
        $query .= "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'";
        $query .= "   ORDER BY sm1.movement_date DESC LIMIT 1) as stocktake_date,";
        // Last stocktake qty
        $query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1";
        $query .= "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'";
        $query .= "   ORDER BY sm1.movement_date DESC LIMIT 1), 0) as stocktake_qty,";
        // Deliveries since last stocktake
        $query .= " COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2";
        $query .= "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'";
        $query .= "   AND sm2.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as deliveries_since,";
        // Stockouts since last stocktake
        $query .= " COALESCE((SELECT SUM(sm3.qty) FROM stock_movement sm3";
        $query .= "   WHERE sm3.stock_id = s.id AND sm3.movement_type = 'stockout'";
        $query .= "   AND sm3.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as stockouts_since,";
        // Damaged since last stocktake
        $query .= " COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4";
        $query .= "   WHERE sm4.stock_id = s.id AND sm4.movement_type = 'damaged'";
        $query .= "   AND sm4.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as damaged_since,";
        // Current level = stocktake + deliveries - stockouts - damaged
        $query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1";
        $query .= "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'";
        $query .= "   ORDER BY sm1.movement_date DESC LIMIT 1), 0)";
        $query .= " + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2";
        $query .= "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'";
        $query .= "   AND sm2.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0)";
        $query .= " - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4";
        $query .= "   WHERE sm4.stock_id = s.id AND sm4.movement_type IN ('stockout','damaged')";
        $query .= "   AND sm4.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as current_qty";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
