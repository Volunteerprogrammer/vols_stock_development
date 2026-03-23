<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockCategoryManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Category";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockCategoryTable $table) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
}
