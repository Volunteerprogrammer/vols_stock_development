<?php
namespace fw\view\body;
use \lib\StdLib as lib;
abstract class HTMLBody
{
    private $trace = false;
    private $bodytag = "<body>\n";
    private $topbar;
    private $navbar;
    private $leftbar;
    private $main;
    private $rightbar;
    private $footer;
    public function __construct(){
        if ($this->trace ) { echo gtab(0)."Enter ".__METHOD__."<br>\n"; }
    }
    protected abstract function __destruct();
    public abstract function init(\fw\session\WebSession $session,\fw\view\form\Form $form);
    // public abstract function render();
    // protected abstract function setBodyTag();
    // protected abstract function setheader();
    // protected abstract function setLeftBar();
    // protected abstract function setmain();
    // protected abstract function setRightBar();
    // protected abstract function setFooter();
    protected function renderdialog($title,$message){
        $content = "";
        if (trim($message) !== "") { 
            $content = <<<HTML
            <div id="openbodydialog" title="{$title}">{$message}</div>
            <script>
                $("#openbodydialog" ).dialog({
                    autoOpen: false,
                    modal: true,
                    height: 400,
                    width: 500,
                    closeOnEscape: true, 
                    position: { my: "center", at: "center", of: window  },
                    buttons:[{
                                text: "CLOSE",
                                click: function() {
                                    $(this).dialog( "close" );
                                }
                            }]
                });
                $(function() { // on load
                  $( "#openbodydialog" ).dialog( "open" );
                });
            </script>
            HTML;
        }
        return $content;
    }
}