<?php
namespace app;
use \lib\StdLib as lib;
class App extends \fw\app\App
{
	protected $trace = false;
	public function __construct(protected \fw\exception\ErrorHandler $errorhandler,
								protected \fw\session\WebSession $session ,
								protected \app\http\RequestHandler $requesthandler,
								protected \database\MySqlDB $db
								) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
		parent::__construct();
	}
	public function go($trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        try {
       		$this->requesthandler->init($this->errorhandler,$this->session,$this->db);
        	$result = $this->requesthandler->processrequest($trace);
        } catch (\Exception $e) {
            die(__METHOD__." : ".$e->__toString());
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $result;
	}
}