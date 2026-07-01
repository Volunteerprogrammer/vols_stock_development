<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockEventSummaryManager extends \fw\controller\manager\StdManager
{
    private $trace          = false;
    protected $name         = "Stock Event History";
    protected $db;
    protected $linkedobject = "";
    private $location_id = '';
    private $from        = '';
    private $to          = '';

    public function __construct(
        protected \apptable\StockEventTable    $table,
        protected \apptable\StockLocationTable $locationtable
    ) {}

    public function init($session, $trace=false) {
        parent::init($session);
        $this->locationtable->init($this->db, $this->user_id);
    }

    public function setlocation($location_id) { $this->location_id = $location_id; }
    public function setdaterange($from, $to)  { $this->from = $from; $this->to = $to; }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }

        $locations = []; $loc_n = 0;
        $this->locationtable->selectall($locations, $loc_n, "name", $trace);

        if ($this->from !== '' && $this->to !== '') {
            $success = $this->table->geteventsummary($this->location_id, $this->from, $this->to, $datafields, $numrows, $trace);
        } else {
            $datafields = [];
            $numrows    = 0;
            $success    = true;
        }
        $this->alldata = $datafields;
        $parents = [
            'locations'   => $locations,
            'location_id' => $this->location_id,
            'from'        => $this->from,
            'to'          => $this->to,
        ];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
