<?php
    $tc = 0; // this sets the debugging inset level in the gtab() function below
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DS',DIRECTORY_SEPARATOR);
    define('APP_START',microtime(true));
    define('ROOT_DIR',dirname(__FILE__).DS);
    define('FW_DIR',sprintf("%svendor%sfw%s",ROOT_DIR,DS,DS));
    define('APP_DIR',sprintf( '%sapp%s',ROOT_DIR,DS));
    try {
        // set up the autoloader
        require sprintf('%sbootstrap%sbootstrap.php',FW_DIR,DS);
        // next, set up the factory for all future class instantiation
        $factory = new fw\factory\ClassFactory();
        // create the app
        $app = $factory->getClass('app\app');
        // process the request
        $ob = "";
        $result = $app->go(false);
        // return anything in the output buffer then the application output
        echo $result;
    } catch (Exception $e) {
        die("Caught Exception in index.php: ".$e->getMessage());
    }
    function gtab($direction=0){
        // During $trace-triggered debugging of nested function calls, this function adds indents 
        // before the debugging info. It allow for progressively indented display during nested function calls,
        // making the interpretation easier.
        // This is not implemented in all classes, and its use should be extended as required
        // Example: 
        //     function fname($trace=false) {
        //         if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        //         // code...
        //         if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        //         return true;
        //     }
        global $tc;
        $sp = "&nbsp;&nbsp;";
        switch ($direction) {
            case -1: echo str_repeat($sp,$tc--); break; 
            case  1: echo str_repeat($sp,++$tc); break;
            default: echo str_repeat($sp,  $tc); break;
        }
        $tc = $tc<0?0:$tc;
    }