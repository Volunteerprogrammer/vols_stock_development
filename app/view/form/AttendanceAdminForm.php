<?php
namespace app\view\form;
use \app\library\AppLib as vlib;
use \lib\StdLib as lib;
class AttendanceAdminForm extends AttendanceForm {
    private $trace= false;
    protected $officeview=true;
    protected function sessionselector($sessions,$targetsession_id) {
        $options = [];
        return $this->component->renderdropdown("sessionselector",1,$options,values:$sessions,selection:$targetsession_id);
    }
}