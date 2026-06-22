<?php
namespace fw\factory;
use \lib\StdLib as lib;
/*é*/
class ClassFactory
{
/*    ==============================================================================
/*	  This class is responsible for instantiating all classes, other than itself.
/*		Based in the principle of dependency injection,
/*			>the NEW reserved word should not be used anywhere but in this class (and the script that creates this class).
/*			>each class in the domain should require all classes on which it is dependent to be passed to it, instantiated, as
/*			 parameters to its __construct(). Dependencies include the dependencies of any other class used by the class, recursively.
/*			>only the parent script (index.php?) should call this factory class to instantiate the primary class of the app. All classes used
/*       by the app will be instantiate in this one call.
/*		===============================================================================
*/
	private $trace = false;
	private $i = 0 ;
	private $indent;
	public function __construct() {}
	/*======================================================================================= THE CLASS BUILDING TOOLS*/
	private function getargvalue($arg=[]) { //
		if ($this->trace) { echo $this->indent($this->i)."Enter ".__METHOD__."($arg) <br>"; }
		$return = null;
		$this->adj_i(8);
		try {
//			if ($this->trace) { echo $this->indent($this->i)."Enter ".__METHOD__." ((".$arg->getName(),")) <br>";}
			$reflectiontype = $arg->getType();
//			$reflector = new ReflectionClass($arg);
			// $rm = new $rmname($cn,'__construct');
			if ( !empty($reflectiontype)) {
				$ok = 1;
				try {
					$obj = new $reflectiontype();
					unset($obj);
				} catch(\Exception $e) {
					$ok = 0;
				}
				if ($ok) {
//				if ($reflector->isInstantiable()) { // we perform a recursive NEW on this parameter class
					if ($this->trace) { echo $this->indent($this->i)."Need recursive NEW on ".$reflectiontype."<br>"; }
					$return = $this->instantiateclass((string) $reflectiontype);
				} else {
					if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__." class not instantiatable.<br>".
							$reflectiontype."<br>".gettype($arg->getName())."<br>"; }
				}
			} else if ($arg->isOptional()) { // just return the optional value
				if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__." default value<br>"; }
				$return = $arg->getDefaultValue();
			} else if (!is_null($arg->hasType())) { // php or user-defined?
				// this should not happen - class __construct() parameters should be instantiable classes or optional
				// but just in case we'll pass an appropriate minimal value to cover our back
				// (on reflection, it would probably be better to call out this parameter by failing at this point)
				$argtype = $arg->getType();
				if (!is_null($argtype)) {
					$typename = $argtype->getName();
					if ($argtype instanceof ReflectionNamedType) {
						if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__."$ arg is $typename : instanceof ReflectionNamedType <br>"; }
						switch ($argtype->getName()) {
							case 'int' : return 0;
							case 'string' : return '';
							case 'array' : return array();
							case 'bool' : return FALSE;
							case 'float' : return 0;
							case 'callable' : return function(){};
							default: return '';
						}
					} else {
						if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__."$ arg is $typename<br>"; }
						$return = '';
					}
				} else {
					if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__."$ argtype is NULL<br>"; }
					$return = '';
				}
			} else {
				if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__."$ argtype has no type<br>"; }
				$return = '';
			}
			$this->adj_i(-8);
			return $return;
	   } catch (\Exception $e) {
	        die('Caught exception in '.__METHOD__."($arg)<br>".$e->getMessage());
	   }
	}

	private function instantiateclass($cn) {
		if ($this->trace) { echo $this->indent($this->i)."Enter ".__METHOD__."($cn) <br>"; }
		$pa = array();
		$paindex = 0;
		try {
			$rmname = "ReflectionMethod";
			$rm = new $rmname($cn,'__construct');
			$args = $rm->getParameters();
			$this->adj_i(8);
			if (count($args)) {
				foreach($args AS $arg)
				{
					if ($this->trace) { echo  $this->indent($this->i).$cn." args[".$paindex."] = ",$arg,"<br>";}
					$pa[$paindex++]	= $this->getargvalue($arg);
				}
			} else {
				if ($this->trace) { echo $this->indent($this->i)."No Arguments<br>";}
			}
				if ($this->trace) { echo $this->indent($this->i)."Exit ".__METHOD__."  >> classname = $cn (ALL GOOD!)<br>"; }
			$this->adj_i(-8);
			return new $cn(...$pa);
		} catch (\Exception $e) {
		        die('Caught EXCEPTION in '.__METHOD__." >> classname = $cn <br> ".$e->getMessage()."<br>");
		}
	}
/*======================================================================================= THE CLASS REQUESTS */
	private function indent($i) {
		return str_repeat('&nbsp',$i);
	}
	public function adj_i($inc){
		$this->i += $inc;
	}
	public function getclass($cn = '') 	{
		$this->indent=0;
//		echo str_repeat("=",30)."<br>";
	    return $this->instantiateclass($cn);
	}
}
