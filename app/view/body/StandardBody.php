<?php
namespace app\view\body;
use \lib\StdLib as lib;
class StandardBody extends HTMLBody
{
// this is a generic body for a page that comprises just a form. It's used for MOST pages.
    protected $pagetitle;
    private $trace=false;
    public function render($pagenum,$rights=[],$isadmin=false,$menu="",$errormessage="",$trace=false,$subheading="",$stockalertpopup="")
    {
        // lib::pr($rights);
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $html = '<body>'."\n";
        $html .= '<div id="curtain"></div>';
        if ($errormessage !== "") {
            $html .= $this->renderdialog("Outcome:",$errormessage);
        }
        $html .= '<div id="volsdialog" style="min-width:600px;display:none;"></div>';
        if ($stockalertpopup !== '') {
            $html .= $stockalertpopup;
        }
        if ($pagenum > 0 && $pagenum < 600) {
            $html .= '<a id="helpbtn" href="?p=600#help_'.(int)$pagenum.'" target="_blank" title="Help for this page" '
                   . 'style="position:fixed;bottom:18px;right:18px;width:32px;height:32px;border-radius:50%;'
                   . 'background:#4a7fbf;color:#fff;font-size:18px;font-weight:bold;text-decoration:none;'
                   . 'display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.3);'
                   . 'z-index:900;" >?</a>';
        }
        $html .= '<div id="content_panel" class="content_panel">'."\n";
        $html .= $this->form->render($pagenum,'',$subheading,$rights,$isadmin,$menu,$trace)."\n";
        $html .= $this->renderfooter();
        $html .= "</div><!--content_panel-->\n";
        $html .= "</body>\n";
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
    }
}