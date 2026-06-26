<?php
namespace app\view\form;
use \lib\StdLib as lib;
class RoleForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 50;
    protected $inputwidth = 40;
    protected $hintwidth = 10;
    protected $fields = [];
    protected $formname = "roleform";
    protected $objname = "Role";
    protected $parentname = "";
    protected $parentobj = "";
    protected $secondselectorname = "childselector";
    protected $pagenum;
    protected $names;
    protected $pages;
    protected $roleid;
    protected $pageid;
    protected $parents;
    protected $pageactions;
    protected $pageactionroles;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$roles=[],$parents=[],$trace=false,$pageactions=[],$pageactionroles=[],$pages=[]){
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($pageactionroles)) {  // add the role_ids for the user's roles in a new element in each $user array
            $this->addlinkstodata($roles,$pageactions,$pageactionroles,$trace);
        }
        parent::init($session,$roles,$parents,$trace);
        $this->roleid = $this->requestdata["id"]??0;
        $this->pageid = $this->requestdata["pageselector"]??0;
        $this->pages = $pages;
        $this->pageactions = $pageactions;
        $this->pageactionroles = $pageactionroles;
     }
    public function addlinkstodata(&$roles=[],$pageactions=[],$pageactionroles=[],$trace=false) {
        foreach ($roles as &$role) {
            foreach ($pageactions as $pageaction) {
                $rpa=0;
                foreach ($pageactionroles as $pageactionrole) {
                    if (($role["id"] == $pageactionrole["role_id"]) && ($pageaction["id"] == $pageactionrole["pageaction_id"] )) {
                        $rpa = 1;
                        break;
                    }
                }
                $role["pageaction".$pageaction["id"]] = $rpa;
            }
        }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>"",
                                "name"=>"",
                                "cellname"=>"",
                                "rosterindex" => "");
                                // "dowaccess"=>"");
     }
    protected function addtonames($role){
            $this->names[$role["id"]] = $role["name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->component->setwidths (25,40,35);
        $formfields  = $this->component->buildinputrow("name",1,"",'Name','Name',25,64,true,'','');
        $formfields  .= $this->component->buildinputrow("cellname",2,"",'Roster cell text','Cellname',25,64,true,'','');
        $this->component->setwidths (25,20,55);
        $formfields  .= $this->component->buildinputrow("rosterindex",3,"",'Session cell position','rosterindex',5,5,true,hint:"Sets the order of the cells for different Roles in a session.");
        $fn = 4;
// =============================================================================================
        $pagedata = array_combine(array_column($this->pages,"id"),array_column($this->pages,"name"));
        $pageselector = $this->component->renderdropdown("childselector",1,$buildoptionlist,false,false,false,false,$pagedata,'21',false,'vols-form-select nondatainput','',false);
        $buttons = ["rightid"=>"showrowsbtn","righttext"=>"Show LINKED","rightdata"=>" data-state='all'","leftid"=>"exportrightsbtn","lefttext"=>"Export CSV","leftdata"=>"","leftscript"=>""];
        $buildoptionlist = '';
        $heading = "<span id='statustextspan'>ALL</span> Rights on the {$pageselector} page.";
        $formfields .= $this->component->rendersectionheading($heading,buttons:$buttons);

        $checkboxes = '';
                $oddevenclass = "vols-row-even";
                $lastname = $thispage = "";
                $this->component->setwidths (30,65,5);
                $hiddencheckboxes = "";
                foreach ($this->pageactions as $id=>$pageaction) {
                    $thispage = substr($pageaction["name"],0,strpos($pageaction["name"],":"));
                    $rowclass = 'vols-tablerow '; // 
                    if ($thispage !== $lastname) {
                        $oddevenclass .=  ($oddevenclass==="vols-row-odd")?'vols-row-even':'vols-row-odd';
                        $rowclass .= $oddevenclass; // 
                    }
                    $rowdata["pageid"] = $pageaction['pageid'];
                    $pageactionname = "link_pageaction".$pageaction["id"];
                    $formfields .= $this->component->buildcheckboxrow($pageactionname,$pageaction["id"],"",true,$fn++,$pageaction["actionname"],'',false,false,false,false,$rowclass,$rowdata);
                    $hiddencheckboxes .= '<input type="hidden" name="'.$pageactionname.'"  value=false />';
                    $lastname = $thispage;
                 }
                 // $formfields .= "</div>";
                $this->preparecommontop(hiddeninputs:$hiddencheckboxes,selecttext:$this->roleid);
        return $formfields;     
     }
    public function formscript() {
        $disablescript = "";
        $onloadscript = <<<JS

                // the children in the linked list in this page have another parent - page - so we haveto activate the 
                // second filter on the list
                setfieldinactivestatus ('#childselector',false);
                jQuery('#childselector').change(function(){
                    showhidepages();
                });                
        JS;
        if ($this->roleid) {
            $onloadscript .= "\njQuery('#recordselector').val('{$this->roleid}').trigger('change');\n";
        }
        if ($this->pageid) {
            $onloadscript .= "\n~jQuery('#childselector').val('{$this->pageid}').trigger('change')\n";
        }

        $postloadfieldsscript = "showhidepages();";
        $postclearfieldsscript = <<<JS

            jQuery("input[type='checkbox']").prop("checked",false);
            $('input:radio').each(function () { $(this).prop('checked', false); });
        JS;
        $presavescript = <<<JS

                    jQuery("#formerror").html("") ;
        JS;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //$postupdatescript=
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript,
                                    $disablescript,
                                    $onloadscript
                                    ); 
        $rolesB64   = base64_encode(json_encode(array_values($this->alldata)));
        $actionsB64 = base64_encode(json_encode(array_values($this->pageactions)));
        $script .= "var _rightsRoles=JSON.parse(atob('{$rolesB64}'));var _rightsActions=JSON.parse(atob('{$actionsB64}'));\n";
        $script .= <<<JS
            jQuery('#exportrightsbtn').on('click', function() {
                // Build ordered page list and unique action columns from pageactions data
                var pageOrder = [], pageNames = {}, actionCols = [];
                _rightsActions.forEach(function(a) {
                    if (!pageNames[a.pageid]) {
                        pageNames[a.pageid] = (a.name.split('&nbsp; --- &nbsp;')[0] || '').trim();
                        pageOrder.push(a.pageid);
                    }
                    if (actionCols.indexOf(a.actionname) === -1) { actionCols.push(a.actionname); }
                });
                actionCols.sort();

                var header = ['"Role"','""','"Page"'].concat(actionCols.map(function(c){ return '"'+c+'"'; })).join(',');
                var lines  = [header];
                _rightsRoles.forEach(function(role) {
                    var roleLines = [];
                    pageOrder.forEach(function(pid) {
                        var cells = [], hasY = false;
                        actionCols.forEach(function(col) {
                            var pa = _rightsActions.find(function(a){ return a.pageid==pid && a.actionname===col; });
                            var y  = pa && role['pageaction'+pa.id]==1;
                            if (y) { hasY = true; }
                            cells.push(y ? '"Y"' : '""');
                        });
                        if (hasY) {
                            roleLines.push(['""','""', '"' + pageNames[pid].replace(/"/g,'""') + '"'].concat(cells).join(','));
                        }
                    });
                    if (roleLines.length) {
                        lines.push('');
                        lines.push('"' + role.name.replace(/"/g,'""') + '",""');
                        roleLines.forEach(function(r){ lines.push(r); });
                    }
                });
                var csv  = lines.join('\\r\\n');
                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
                var a    = document.createElement('a');
                a.href     = URL.createObjectURL(blob);
                a.download = 'rights_by_role.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(a.href);
            });
            function showhidepages() {
                const selectedpageid = jQuery("#childselector").find(":selected").val();
                setchildselectorheadingtext("pageid",selectedpageid);
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
                // only required if the form property 'idselection' = false 
            }
            function getchildnames() { return ["pageaction","Pageactions"]}
        JS;
        return $script;
     }
}
