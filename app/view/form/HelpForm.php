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
        $adminlink = in_array($mm::HELPADMINPAGE.'||UPDATE', $rights) ? '<a href="?p='.$mm::HELPADMINPAGE.'" class="help-admin-link">Edit Help Content</a>' : '';

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
            if (in_array($mm::HELPADMINPAGE.'||UPDATE', $rights)) {
                $html .= '<p>Use the <a href="?p='.$mm::HELPADMINPAGE.'">Help Content Admin</a> page to add content.</p>';
            }
            $html .= '</div>';
        } else {
            // Table of contents — only shown when displaying multiple sections
            if (count($visible) > 1) {
                $html .= '<div class="help-toc vols-tablerow" style="padding:14px 20px;background:#f4f8fd;border-bottom:1px solid #c8d8ee;">';
                $html .= '<strong style="font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#4a7fbf;">Contents</strong>';
                $html .= '<ul style="margin:8px 0 0 18px;line-height:1.9;">';
                foreach ($visible as $item) {
                    $html .= '<li><a href="#help_'.(int)$item['page_id'].'" style="color:#2c5282;text-decoration:none;">'
                           . htmlspecialchars($item['title']).'</a></li>';
                }
                $html .= '</ul></div>';
            }

            // Sections
            foreach ($visible as $item) {
                $pid   = (int)$item['page_id'];
                $title = htmlspecialchars($item['title']);
                $content = $item['content'];
                $body  = (strpos($content, '<') !== false)
                       ? $content
                       : nl2br(htmlspecialchars($content));
                $html .= '<section id="help_'.$pid.'" class="help-section vols-tablerow" style="padding:16px 20px 20px;border-top:3px solid #4a7fbf;">';
                $html .= '<div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px;">';
                $html .= '<h2 style="margin:0;font-size:1.15em;color:#2c5282;letter-spacing:0.02em;">'.$title.'</h2>';
                $html .= '<a href="#help-display" style="font-size:0.78em;color:#4a7fbf;text-decoration:none;white-space:nowrap;margin-left:16px;margin-right:18px;" title="Back to top">&#9650; top</a>';
                $html .= '</div>';
                $html .= '<div class="help-content" style="line-height:1.7;color:#333;">'.$body.'</div>';
                $html .= '</section>';
            }
        }

        $html .= '</div>'; // #help-display
        return $html;
    }
}
