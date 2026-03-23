<?php
namespace fw\view\head;
use \lib\StdLib as lib;
abstract class HTMLHead
{
    private $trace = false;
    protected $title = "";
    protected $meta = "";
    protected $links = "";
    protected $script = "";
    protected $style = "";
    public function __construct(){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
    }

    abstract function init($session,$pagenum,$targetpage);
    abstract function __destruct();
    abstract protected function settitle();
    abstract protected function setmeta();
    abstract protected function setlinks();
    abstract protected function setscript();    
    abstract protected function setstyle();     
    abstract protected function render();
}
