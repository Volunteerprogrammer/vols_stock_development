<?php
namespace app\view\form;
use \lib\StdLib as lib;
class HelpForm extends \fw\view\form\Form
{
    private $trace = false;
    protected $formname = "helpform";
    private array $helpitems = [];

    public function __construct() {}

    public function init($session, $data=[], $parents='', $trace=false) {
        parent::init($session);
        $this->helpitems = is_array($data) ? $data : [];
    }

    public function render($pagenum='', $nextpage='', $subheading='', $rights=[], $isadmin=false, $menu='', $trace=false) {
        $visible = [];
        foreach ($this->helpitems as $item) {
            $pid = (int)$item['page_id'];
            if ($isadmin || in_array($pid.'||VIEW', $rights)) {
                $visible[] = $item;
            }
        }

        $mm        = $this->menumanager;
        $adminlink = $isadmin ? '<a href="?p='.$mm::HELPADMINPAGE.'" class="help-admin-link">Edit Help Content</a>' : '';

        $html  = '<div id="help-display" class="vols-table">';
        $html .= '<div class="vol-form-headingcontainer"><div class="headingrowwrap">';
        $html .= '<span class="vols-form-pageheading">Help &amp; Documentation</span>';
        if ($adminlink) {
            $html .= '&nbsp;&nbsp;'.$adminlink;
        }
        $html .= '</div></div>';

        if (empty($visible)) {
            $html .= '<div class="vols-tablerow" style="padding:20px;">';
            $html .= '<p>No help content has been written for the pages you can access yet.</p>';
            if ($isadmin) {
                $html .= '<p>Use the <a href="?p='.$mm::HELPADMINPAGE.'">Help Content Admin</a> page to add content.</p>';
            }
            $html .= '</div>';
        } else {
            // Table of contents
            $html .= '<div class="help-toc vols-tablerow" style="padding:12px 20px;">';
            $html .= '<strong>Contents</strong><ul style="margin:8px 0 0 16px;">';
            foreach ($visible as $item) {
                $html .= '<li><a href="#help_'.(int)$item['page_id'].'">'.htmlspecialchars($item['title']).'</a></li>';
            }
            $html .= '</ul></div>';

            // Sections
            foreach ($visible as $item) {
                $pid   = (int)$item['page_id'];
                $title = htmlspecialchars($item['title']);
                $body  = nl2br(htmlspecialchars($item['content']));
                $html .= '<section id="help_'.$pid.'" class="help-section vols-tablerow" style="padding:16px 20px;border-top:1px solid #ddd;">';
                $html .= '<h2 style="margin:0 0 10px;">'.$title.'</h2>';
                $html .= '<div class="help-content" style="line-height:1.6;">'.$body.'</div>';
                $html .= '</section>';
            }
        }

        $html .= '</div>'; // #help-display
        return $html;
    }
}
