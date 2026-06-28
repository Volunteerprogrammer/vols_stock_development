<?php
namespace apptable;
class RosterTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id = "null") {
        parent::init($db, $user_id);
        $this->fields = [
            "id"                => "",
            "name"              => "",
            "maxcolumns"        => "",
            "autoextendtasks"   => "",
            "leadtime"          => "",
            "publishedleadtime" => "",
            "startdate"         => "",
            "enddate"           => "",
            "sessiondepth"      => "",
        ];
    }
}
