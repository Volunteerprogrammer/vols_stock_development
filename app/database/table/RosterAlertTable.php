<?php
namespace apptable;
class RosterAlertTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id = "null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = [
            "id"           => "",
            "task_role_id" => "",
            "period"       => "",
            "level"        => "",
        ];
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }
}
