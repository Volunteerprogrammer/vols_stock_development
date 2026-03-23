<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Item";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockTable          $table,
                                protected \apptable\StockCategoryTable  $categorytable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->categorytable->init($this->db);
    }

    protected function getparents(&$parents, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $categories = [];
        $numrows = 0;
        $success = $this->categorytable->selectall($categories, $numrows, "Name", $trace);
        $parents = $categories;
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
    }
}
