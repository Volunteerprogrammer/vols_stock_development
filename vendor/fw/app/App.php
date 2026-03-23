<?php
namespace fw\app;

abstract class App
{
	protected $config=array("fw"=>array(),"app"=>array());
	protected function __construct() {
	    // load the configuation files into global var arrays
	    try {
	        $this->config["fw"] = parse_ini_file(sprintf("%sconfig%sconfig.php",FW_DIR,DS));
	    } catch (\Exception $e) {
	        die('Caught exception in fw\app\app :'.$e->getMessage());
	    }
	}
}