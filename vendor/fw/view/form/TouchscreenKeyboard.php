<?php 
namespace fw\view\form;
use \lib\StdLib as lib;
class TouchscreenKeyboard {
    private $keys;
    public function __construct() {
        $this->keys = [
            ['Q','W','E','R','T','Y','U','I','O','P'],
            ['A','S','D','F','G','H','J','K','L'],
            ['Z','X','C','V','B','N','M','.','-'],
            ['clear', 'space', 'backspc']
        ];
    }

    public function render($inputwidth="10" ) {
        $html  = "<div id='keyboardcontainer'>";
        $html .= "<div id='inputcontainer'><input size='{$inputwidth}' type='text' id='kbdinput' readonly ></div>";
        $html .= "<div id='keyboard'>";
        $rowid = ["QWE","ASD","ZXC","SPACE"];
        $r = 0;
        foreach ($this->keys as $row) {
            $html .= "<div class='key-row {$rowid[$r++]}'>";
            $html .= ($r==2)?"&nbsp;":"";
            $html .= ($r==3)?"&nbsp; &nbsp; ":"";
            foreach ($row as $key) {
                $label = $key;
                $html .= "<button class='key' data-key='{$key}'>{$label}</button>";
            }
            $html .= "</div>";
        }
        $html .= "</div>";
        $html .= "</div>";
        // Include CSS and JS (assumes you have keyboard.css and keyboard.js in your project)
        $html .= <<<JS
        
            <script>
                jQuery(function () {
                    jQuery('.key').on('click touchstart', function(e) {
                        e.preventDefault();
                        var key = jQuery(this).data('key');
                        var input = jQuery('#kbdinput');
                        var current = input.val();

                        if (key === 'backspc') {
                            input.val(current.slice(0, -1));
                        } else if (key === 'clear') {
                            input.val('');
                        } else if (key === 'space') {
                            input.val(current + ' ');
                        } else {
                            input.val(current + key);
                        }
                        input.trigger("change");
                    });
                });            
            </script>
        JS;
        return $html;
    }
}