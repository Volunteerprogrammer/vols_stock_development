<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('P',["#\n {3} *#",       "#ray\n#",          "#\n *\)#",      "# *\=\> *#",   "# {2} *#"  ,"#\n *\)#", "#\( #", "# \)#"   ]);
    define('R',[" ",               "ray",               ")",             "=",            " "         ,")"       , "(", ")" ]);
    define('DS',DIRECTORY_SEPARATOR);
    define('APP_START',microtime(true));
    define('ROOT_DIR',dirname(__FILE__).DS);
    define('FW_DIR',sprintf("%svendor%sfw%s",ROOT_DIR,DS,DS));
    define('APP_DIR',sprintf( '%sapp%s',ROOT_DIR,DS));
    // set_error_handler("handlewarnings",E_ALL|E_WARNING|E_NOTICE );
    try {
        // set up the autoloader
        require sprintf('%sbootstrap%sbootstrap.php',FW_DIR,DS);
        // next, set up the factory for all future class instantiation
        $factory = new fw\factory\ClassFactory();
        // create the app
        $reviewer = $factory->getClass('app\daemon\RosterReview');
        // process the request        
        $reviewer->do_review(false);
        die(); // output intended for the cron email report
    } catch (\Exception $e){
        die($e->getMessage());
    }
    function handlewarnings($errno, $errstr, $errfile, $errline) {
        if (0 === error_reporting()) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }