<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StocktakeManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stocktake";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockTable         $table,
                                protected \apptable\StockMovementTable $movementtable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->movementtable->init($this->db);
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->getstockwithlevels($datafields, $numrows, $trace);
        $this->alldata = $datafields;
        $this->makenames($trace);
        $parents = [];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }

    protected function makenames($trace=false) {
        foreach ($this->alldata as $record) {
            $this->names[$record["id"]] = $record["Name"];
        }
    }

    public function performaction($action, &$outcomemessage, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." action={$action}<br>"; }
        if ($action !== 'stocktake') {
            $outcomemessage = "Unknown action: {$action}";
            return false;
        }
        $data = $this->requestdata;
        $success = true;
        $saved = 0;
        foreach ($data as $key => $value) {
            if (substr($key, 0, 4) === 'qty_' && $value !== '' && $value !== null) {
                $stock_id = (int)substr($key, 4);
                $unit     = $data["unit_{$stock_id}"] ?? '';
                $unit_qty = $data["unit_qty_{$stock_id}"] ?? 1;
                $id       = 0;
                $success  = $success && $this->movementtable->insertstocktake($stock_id, $value, $unit, $unit_qty, $id, $outcomemessage, $trace);
                $saved++;
            }
        }
        if ($success && $saved === 0) {
            $outcomemessage = "No quantities were entered — nothing saved.";
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." saved={$saved}<br>"; }
        return $success;
    }
}
