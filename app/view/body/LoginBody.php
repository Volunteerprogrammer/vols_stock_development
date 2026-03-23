<?php
namespace app\view\body;
use \lib\StdLib as lib;
class LoginBody extends HTMLBody
{
    protected $pagetitle;
    private $trace=false;
    public function render($fill,$fill2,$admin,$fill3="",$errormessage="")     {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $html = '<body>'."\n";
        $html .= '<div id="content_panel" class="content_panel">'."\n";
        $html .= $this->form->render($errormessage);        
        $html .= '</div><!--content_panel-->'."\n";
        $html .= "</body>\n";
        if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
        return $html;
    }
}