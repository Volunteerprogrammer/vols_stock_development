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
        $query  = "SELECT s.id, s.Name, s.Code, s.category_id, sc.Name as category_name,";
        $query .= " COALESCE((";
        $query .= "   SELECT sm1.qty FROM stock_movement sm1";
        $query .= "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'";
        $query .= "   ORDER BY sm1.id DESC LIMIT 1";
        $query .= " ), 0)";
        $query .= " + COALESCE((";
        $query .= "   SELECT SUM(sm2.qty) FROM stock_movement sm2";
        $query .= "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'";
        $query .= "   AND sm2.id > COALESCE((";
        $query .= "     SELECT MAX(sm3.id) FROM stock_movement sm3";
        $query .= "     WHERE sm3.stock_id = s.id AND sm3.movement_type = 'stocktake_adjustment'";
        $query .= "   ), 0)";
        $query .= " ), 0)";
        $query .= " - COALESCE((";
        $query .= "   SELECT SUM(sm4.qty) FROM stock_movement sm4";
        $query .= "   WHERE sm4.stock_id = s.id AND sm4.movement_type IN ('stockout','damaged')";
        $query .= "   AND sm4.id > COALESCE((";
        $query .= "     SELECT MAX(sm5.id) FROM stock_movement sm5";
        $query .= "     WHERE sm5.stock_id = s.id AND sm5.movement_type = 'stocktake_adjustment'";
        $query .= "   ), 0)";
        $query .= " ), 0) as current_qty";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
