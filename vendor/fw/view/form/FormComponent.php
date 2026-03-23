<?php
namespace fw\view\form;
use \lib\StdLib as lib;
abstract class FormComponent {
    private $trace = false;                
    protected $missingfields = array();  // array of fieldnames for required fields with no data
    protected $dataerrors = array();  // name=>errormsg pairs for all validated fields in the form
    protected $dberrors = array();  
    protected $required  = array();
    protected $trimdelim;
    protected $err_message;
    protected $odd=false;
    protected $promptwidth;
    protected $inputwidth;
    protected $hintwidth;
    protected $formname;
    protected $groupdelimiter = "^^";
    protected $recorddelimiter = "!!";
    protected $fielddelimiter = "|";
    protected $norecordstoedit;
    abstract public function __construct();
    abstract protected function renderproblemsheader($trace=false); 
    abstract protected function renderhint($hint,$class='') ;
    abstract protected function renderrowerror($error,$class='') ;
    abstract protected function rendersectionheading($heading,$headingclass,$subheading="",$subheadingclass="",$rowid='',$actionrow="",$width='',$id='',$cellclass='') ;
/*  abstract protected function renderprompt($prompt,$required=false,$promptclass='')*/
    abstract protected function renderprompt($id,$prompt,$required=false,$cellclass=''); 
    abstract protected function buildsimpleoptionlist($array,&$optionarray='',$trace=false); 
    abstract protected function buildoptionlist($data,&$ids='',&$names=[],$valfield="id",$orderby='name',$sequenceconstants=SORT_ASC,$trace=false); 
    abstract protected function rendermulti($sectionheading,$rowid,$prompt1,$prompt2,$notmine,$mine,$size,$identifier='',$mid='',$hidden=false,$trace=false,$selectandhidesingleoptions=true,$sectionheadclass='',$formclass='') ;
    abstract protected function rendercheckbox($fieldname,$value,$checked=0,$text='',$lefttext=false,$fieldnum=0,$autofocus=false,$inputclass='',$divclass='',$required=false,$readonly=false,$disabled=false) ;
    abstract protected function renderradiobuttons($fieldname,$buttons,$curval,$radioclass='',$fnum='',$horizontal=false,$id='',$multicolumn=false) ;
    abstract protected function renderdropdown($fieldname,$size,&$options,$autofocus,$disabled,$required,$multiple,$values,$selection, $selectontext=false,$selectclass='',$divclass='',$trace=false,$fnum='') ;
    abstract protected function rendertextinput($fieldname,$size,$maxlength,$placeholder,$autofocus,$value,$inputclass='',$divclass='',$fnum=0,$required=false,$readonly=false,$disabled=false,$rows=1); 
/*========================================================================================= RENDER ONE ROW */
    abstract protected function rendercell($id,$cellcontents,$cellclass,$pcwidth,$styleclass='',$pxwidth=0) ;
    abstract protected function renderformrow($id,$promptid,$prompt,$required,$promptcellclass,$promptstyle,$inputid,$forminput,$inputcellclass,$inputstyle, $hintid, $hint,$hintcellclass,$hintstyle,$errorid, $rowerror, $errorcellclass, $errorstyle, $rowborder,$rowclass='') ;
/*=========================================================================== BUILD AND RENDER ENTIRE ROW - various field types */
    abstract protected function buildselectrow($field,$fieldnum,$listheight,$prompt,$values,$hint,&$optionsout,$autofocus=false,$disabled=false,$required=false,$multiple=false,$rowstyle='',$trace=false) ;
    abstract protected function buildinputrow($field,$fieldnum,$value,$prompt,$placeholder,$cols,$maxlength,$required=false,$hintid='', $hint='',$readonly=false,$disabled=false) ;
    abstract protected function buildtextarearow($field,$fieldnum,$value,$prompt,$placeholder,$cols,$rows,$maxlength,$required=false,$hintid='', $hint='',$readonly=false,$disabled=false) ;
    abstract protected function buildcheckboxrow($field,$value,$text,$lefttext=false,$fieldnum=0,$prompt='', $hint='',$required=false,$readonly=false,$disabled=false) ;
    abstract protected function builddaterow($fieldname,$type,$placeholder,$min,$max,$step,$fnum=0,$autofocus=false,$value='',$prompt='',$hint='',$required=false,$disabled=false,$trace=false);
    abstract protected function renderinputrangerow($rowid,$prompt,$placeholder,$fromid,$toid,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$hint='',$rowclass='',$size=5,$max=5) ;
    abstract protected function renderdaterangerow($rowid,$prompt,$fromdateid,$todateid,$fromfnum,$tofnum,$fromval,$toval,$frommin="",$frommax="",$tomin="",$tomax="",$fromstep="",$tostep="",$hint='',$inputclass='',$rowclass='',$required=false,$readonly=false,$disabled=false,$autofocus=false) ;
    abstract protected function rendertimerangerow($rowid,$prompt,$placeholder,$fromtimeid,$totimeid,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$hint='',$rowclass='',$size=5,$max=5);
    abstract protected function renderlocationrangerow($rowid,$prompt,$fromloc,$toloc,$fromselect,$toselect,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$multiple=false,$hint='') ;
/*========================================================================================= RENDER COMMON TOP AND BOTTOM */
    abstract public function rendercommontop($id,$names,$subheading="",$subheadingclass="",$actionbuttons=[],$nextpage_num=0,$noactionrow=false,$noselection=false,$hiddenfields='',$selecttext='',$menu='',$trace=false) ;
    abstract public function rendercommonbottom($trace=false) ;
}
