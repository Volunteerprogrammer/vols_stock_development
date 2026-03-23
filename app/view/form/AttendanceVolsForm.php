<?php
namespace app\view\form;
use \app\library\AppLib as vlib;
use \lib\StdLib as lib;
class AttendanceVolsForm extends AttendanceForm {
    private $trace= false;
    protected $officeview=false;
    protected function sessionselector() {
        return "";
    }

}