<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class LocationManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Location";
    protected $db;
    protected $linkedobject = "";
    public function __construct(
        protected \apptable\StockLocationTable         $table,
        protected \apptable\StockEventTable            $stockeventtable,
        protected \apptable\StockItemLocationTable     $itemlocationtable,
        protected \apptable\StockCategoryLocationTable $categorylocationtable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->stockeventtable->init($this->db, $this->user_id);
        $this->itemlocationtable->init($this->db, $this->user_id);
        $this->categorylocationtable->init($this->db, $this->user_id);
    }

    // Returns {cat_id: position} map for the given location.
    public function getcategorypositions($location_id, &$positions) {
        $rows = []; $numrows = 0;
        $this->categorylocationtable->getpositionsforlocation($location_id, $rows, $numrows);
        $positions = [];
        foreach ($rows as $row) {
            $positions[(int)$row['stock_category_id']] = (int)$row['position'];
        }
    }

    public function getstockwithtargets($location_id, &$results, &$numrows) {
        return $this->itemlocationtable->getstockwithtargets($location_id, '', $results, $numrows, $this->trace);
    }

    public function settargetqty($stock_id, $location_id, $qty_str, &$errormessage) {
        if ($qty_str === '' || $qty_str === null) {
            return $this->itemlocationtable->deletebystock($stock_id, $location_id, $errormessage);
        }
        return $this->itemlocationtable->upsert($stock_id, $location_id, (int)$qty_str, $errormessage);
    }

    public function setlocationstock($stock_id, $location_id, $target_str, $min_str, &$errormessage) {
        $tqty = ($target_str !== '' && $target_str !== null) ? (int)$target_str : null;
        $mqty = ($min_str    !== '' && $min_str    !== null) ? (int)$min_str    : null;
        if ($tqty === null && $mqty === null) {
            return $this->itemlocationtable->deletebystock($stock_id, $location_id, $errormessage);
        }
        return $this->itemlocationtable->upsertboth($stock_id, $location_id, $tqty, $mqty, $errormessage);
    }

    public function update(&$errormessage="", $trace=false) {
        $success = parent::update($errormessage, $trace);
        if (!$success) return false;

        $location_id   = $this->requestdata['id'] ?? 0;
        if (!$location_id) return true;

        // Save per-item target and minimum quantities.
        $stock_ids_str = $this->requestdata['sil_stock_ids'] ?? '';
        foreach (array_filter(explode(',', $stock_ids_str)) as $raw) {
            $stock_id = (int)$raw;
            if (!$stock_id) continue;
            $target  = $this->requestdata["sil_{$stock_id}"]     ?? '';
            $min     = $this->requestdata["min_qty_{$stock_id}"] ?? '';
            $errm    = '';
            $this->setlocationstock($stock_id, $location_id, $target, $min, $errm);
            if ($errm) $errormessage .= ($errormessage ? '; ' : '') . $errm;
        }

        // Save category positions.
        $cat_ids_str = $this->requestdata['cat_pos_ids'] ?? '';
        foreach (array_filter(explode(',', $cat_ids_str)) as $raw) {
            $cat_id  = (int)$raw;
            if (!$cat_id) continue;
            $pos_str = trim($this->requestdata["cat_pos_{$cat_id}"] ?? '');
            $errm    = '';
            if ($pos_str === '') {
                $this->categorylocationtable->deleteone($cat_id, $location_id, $errm);
            } else {
                $this->categorylocationtable->upsert($cat_id, $location_id, (int)$pos_str, $errm);
            }
            if ($errm) $errormessage .= ($errormessage ? '; ' : '') . $errm;
        }
        return true;
    }

    public function delete(&$errormessage="", $trace=false) {
        $id      = (int)($this->requestdata['id']);
        $results = [];
        $numrows = 0;
        $this->stockeventtable->query_params(
            "SELECT id FROM stock_event WHERE location1_id = ? OR location2_id = ?",
            [$id, $id], $results, $numrows
        );
        if ($numrows > 0) {
            $errormessage = "Cannot delete this location — it is used by $numrows stock event(s). Remove those events first.";
            return false;
        }
        return parent::delete($errormessage, $trace);
    }
}
