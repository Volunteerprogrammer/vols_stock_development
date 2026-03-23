<?php
namespace fw\http;

abstract class RequestHandler  
{
    private $doctype;
    protected $bodysection;
    protected $headsection;

      
    public function __construct() 
	{
        // instantiation of the body is to be done in the subclass so appropriate body subclasses are used
        $page->processforms();
        $page->render();
    }

    public function __destruct() 
	{
    // clean up here
    }
/*
    abstract function processforms
	(
	); 
	
    abstract function render
	(
	); 
*/
}

