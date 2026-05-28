<?php
namespace apptable;
class StockCategoryLocationTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = [
            "stock_category_id" => "",
            "stock_location_id" => "",
            "position"          => "0",
        ];
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    // Insert or update the position for one category at one location.
    public function upsert($category_id, $location_id, $position, &$errormessage) {
        $result = null; $numrows = 0;
        return $this->execute_params(
            "INSERT INTO stock_category_location (stock_category_id, stock_location_id, position)"
            . " VALUES (?, ?, ?)"
            . " ON DUPLICATE KEY UPDATE position = VALUES(position)",
            [(int)$category_id, (int)$location_id, (int)$position],
            $result, $numrows, $errormessage, 1
        );
    }

    // Delete the position row for one category at one location (called when position is cleared).
    public function deleteone($category_id, $location_id, &$errormessage) {
        $result = null; $numrows = 0;
        return $this->execute_params(
            "DELETE FROM stock_category_location WHERE stock_category_id = ? AND stock_location_id = ?",
            [(int)$category_id, (int)$location_id],
            $result, $numrows, $errormessage, 1
        );
    }

    // Returns all rows for a location, keyed by stock_category_id => position.
    public function getpositionsforlocation($location_id, &$results, &$numrows, $trace=false) {
        return $this->query_params(
            "SELECT stock_category_id, position FROM stock_category_location"
            . " WHERE stock_location_id = ? ORDER BY position",
            [(int)$location_id], $results, $numrows, $trace
        );
    }
}
