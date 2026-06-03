<?php
namespace app\view\form;
use \lib\StdLib as lib;
class AttendanceReportView extends \fw\view\form\Form { 
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 45;
    protected $hintwidth = 25;
    protected $fields = [];
    protected $pagename = "Attendance Report";
    protected $formname = "attendancereportview";
    protected $rights;
    protected $sessions;
    public  function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function init($sessn){
        parent::init($sessn);
        $this->requestdata = $this->session->getrequestdata();
        $this->config = $this->session->getconfig();
     }
    public function render($pagenum=0,$nextpage='',$subheading='',$rights=[],$isadmin=false,$menu='',$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->isadmin = $isadmin;
        $content =  <<<HTML
                <div class="greetingcontainer">
                    <div></div>
                    <div class="greeting" >$this->pagename</div> 
                    {$menu}
                </div>
                <div id ="sessiongridcontainer">
                    <div id="headercontainer">{$header}</div>
                    <div id="maincontainer">
                        {$this->component->renderdaterangerow("daterange","Date range","fromdate","todate",1,2)}
                        <div class="vols-tablebody actioncontainer">
                            <div id="editcontainer" class="col3">
                                <div class="vols-tablecell vols-width-100 aligncenter">
                                    <div id="finddata" class="clickable action  doitbg "><span class="underlined">F</span>ind data</div>
                                </div>
                                <div class="vols-tablecell vols-width-100 aligncenter">
                                    <div id="printdetailed" class="clickable action doitbg disabled ">Print <span class="underlined">D</span>etailed</div>
                                </div>
                                <div class="vols-tablecell vols-width-100 aligncenter">
                                    <div id="printsummary" class="clickable action doitbg disabled">Print <span class="underlined">S</span>ummary</div>
                                </div>
                            </div>
                            <div id="detailedreportcontainer" >
                                <div  id="detailedreportheader"><div>
                                <div  id="detailedreportcolumnheaders">
                                    <div class="vols-tablecell vols-width-100 aligncenter">Date</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Event</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Attendances</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Total beneficiaries</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Gender</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Age Category</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Home</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">ATSI</div>
                                    <div class="vols-tablecell vols-width-100 aligncenter">Home</div>
                                </div>
                            </div>
                            <div id="reportcontainer" class="detailedreportcontainer">
                                <div class="vols-tablecell vols-width-100 aligncenter">Date</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Event</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Attendances</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Total beneficiaries</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Gender</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Age Category</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Home</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">ATSI</div>
                                <div class="vols-tablecell vols-width-100 aligncenter">Home</div>
                            </div>
                        </div>
                    </div>
                </div>

        HTML;
        return $content;
     }

    public function buildinputs($rights=[],$trace=false) {
        // lib::pr($this->rights);     

        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $content;
     }
    public function formscript() {
        $script = <<<JS
                jQuery(function() {
                    jQuery("#finddata").on("click",function() {
                        // check date range
                        const fromdatestr  = jQuery("#"+fromdate).val();
                        const todatestr    = jjQuery("#"+todate).val();
                        const fd        = new Date(fromdatestr);
                        const td        = new Date(todatestr);
                        if ((!validateDate($fromdatestr)) ||  (!validateDate($todatestr))) {
                            jQuery.volsdialog('OKMSG', 'Not a valid date.', undefined, undefined, 'Validation');

                        } else if ($fd > $td) {
                            jQuery.volsdialog('OKMSG', 'Dates in incorrect order.', undefined, undefined, 'Validation');
                        } else {
                            // if OK send request
                            setwaitcursor();
                            result = await  doServerRequest('',fromdatestr+'|'+todatestr,'attendancereport');

                            clearwaitcursor();

                        //

                        } 
                    });

                    jQuery("#printdetailed, #printsummary").on("click",function() {
                        let eid = 0;
                        let task = [];
                        const detailed = $(this).prop("id") == "printdetailed";
                        jQuery("#rostercontainer div.taskcontainer").each(function() {
                            task.push(jQuery(this).find("div.taskheading").html());
                            let sessions = [];
                            jQuery(this).find("div.sessioncontainer").each(function () {
                                sessiondate = jQuery(this).find("div.sessiondatetext").html();
                                let sessiondata=[]
                                sessiondata.push(sessiondate)
                                jQuery(this).find("div.volcell").each(function () {
                                    celltext = jQuery(this).html();
                                    sessiondata.push(celltext);
                                }); 
                                sessions.push(sessiondata);
                            }); 
                            task.push(sessions);
                        });
                        nowstr = fw_nowstring(1);
                        footer = "Printed at "+fw_nowstring(0);
                        Print2TaskRoster('ROSTER_'+nowstr,"{$this->config["app"]["DEPARTMENT"]} Roster",task,"{$this->config["app"]["ORGANISATIONNAME"]}",footer);
                    });



                });

        JS;
        return $script;
     }
}
