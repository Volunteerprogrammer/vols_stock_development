<?php
namespace app\controller\manager;
use \lib\StdLib as lib;

class RosterWizardManager extends \fw\controller\manager\StdManager {
    private $trace = false;
    protected $name = "RosterWizard";
    protected $linkedobject = "";

    public function __construct(
        protected \apptable\RosterTable      $table,
        protected \apptable\PageTable        $pagetable,
        protected \apptable\TaskTable        $tasktable,
        protected \apptable\RoleTable        $roletable,
        protected \apptable\TaskRoleTable    $taskroletable,
        protected \apptable\RosterAlertTable $rosteralerttable,
        protected \apptable\UserRoleTable    $userroletable
    ) {}

    public function init($session, $trace = false) {
        parent::init($session);  // sets $this->db, also calls $this->table->init()
        $this->pagetable->init($this->db);
        $this->tasktable->init($this->db);
        $this->roletable->init($this->db);
        $this->taskroletable->init($this->db);
        $this->rosteralerttable->init($this->db);
        $this->userroletable->init($this->db);
    }

    // -------------------------------------------------------------------------
    // Step 1: Create Roster
    // -------------------------------------------------------------------------
    public function createRoster($d, &$errormessage = '') {
        $name = trim($d['name'] ?? '');
        if ($name === '') { $errormessage = 'Roster name is required.'; return false; }

        $esc = $this->pagetable->real_escape_string($name);
        $this->pagetable->query(
            "SELECT id FROM page WHERE name = '{$esc}' AND pagetype = 2 AND id NOT IN (SELECT id FROM roster) LIMIT 1",
            $existing, $existing_count
        );
        if ($existing_count > 0) {
            $page_id = (int)$existing[0]['id'];
        } else {
            $this->pagetable->query(
                "SELECT COALESCE(MAX(pagenumber), 100) + 1 AS next_num FROM page WHERE pagetype = 2",
                $result, $numrows
            );
            $next_num = (int)($result[0]['next_num'] ?? 101);
            $this->pagetable->clear();
            $this->pagetable->setfield('pagenumber',   $next_num);
            $this->pagetable->setfield('name',         $name);
            $this->pagetable->setfield('pagetype',     2);
            $this->pagetable->setfield('unrestricted', 0);
            $ok = $this->pagetable->insert(true, $new_page_id, false, $errormessage);
            if (!$ok) { return false; }
            $page_id = (int)$new_page_id;
        }

        $this->pagetable->query("SELECT pagenumber FROM page WHERE id = {$page_id}", $pr, $pn);
        $page_number = (int)($pr[0]['pagenumber'] ?? 0);

        $nulldate = fn($v) => (trim((string)$v) === '') ? null : $v;
        $ok = $this->table->execute_params(
            "INSERT INTO roster (id, name, maxcolumns, autoextendtasks, leadtime, publishedleadtime, startdate, enddate, sessiondepth)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $page_id,
                $name,
                $d['maxcolumns']        ?: null,
                (int)($d['autoextendtasks'] ?? 0),
                $d['leadtime']          ?: null,
                $d['publishedleadtime'] ?: null,
                $nulldate($d['startdate'] ?? ''),
                $nulldate($d['enddate']   ?? ''),
                $d['sessiondepth']      ?: null,
            ],
            $result, $numrows, $errormessage, 1
        );
        if (!$ok) { return false; }
        return ['roster_id' => $page_id, 'page_number' => $page_number];
    }

    // -------------------------------------------------------------------------
    // Step 2: Tasks
    // -------------------------------------------------------------------------
    public function addTask($d, &$errormessage = '') {
        $name      = trim($d['name']      ?? '');
        $roster_id = (int)($d['roster_id'] ?? 0);
        if ($name === '' || !$roster_id) { $errormessage = 'Task name and roster are required.'; return false; }

        $totime  = fn($v) => (trim((string)$v) === '') ? '00:00:00' : trim($v);
        $nullstr = fn($v) => (trim((string)$v) === '') ? null : trim($v);
        $toint   = fn($v) => (int)$v;  // NOT NULL int columns default to 0
        $ok = $this->tasktable->execute_params(
            "INSERT INTO task
                (page_id, name, starttime, endtime, recurrence,
                 taskgroup, groupindex, cellsperrow,
                 dailyoption, dailyinterval,
                 weeklyinterval, weeklydow, weeklyindex,
                 monthlyoption, monthlydayofmonth, monthlyinterval0, monthlywhichdow, monthlydow, monthlyinterval1)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $roster_id,
                $name,
                $totime($d['starttime'] ?? ''),
                $totime($d['endtime']   ?? ''),
                $d['recurrence'] ?: 'Once-only',
                $toint($d['taskgroup']   ?? 0),
                $toint($d['groupindex']  ?? 0),
                $toint($d['cellsperrow'] ?? 0),
                $toint($d['dailyoption']   ?? 0),
                $toint($d['dailyinterval'] ?? 0),
                $toint($d['weeklyinterval'] ?? 0),
                $toint($d['weeklydow']      ?? 0),
                $toint($d['weeklyindex']    ?? 0),
                $toint($d['monthlyoption']       ?? 0),
                $toint($d['monthlydayofmonth']   ?? 0),
                $toint($d['monthlyinterval0']    ?? 0),
                $toint($d['monthlywhichdow']     ?? 0),
                $toint($d['monthlydow']          ?? 0),
                $toint($d['monthlyinterval1']    ?? 0),
            ],
            $result, $numrows, $errormessage, 1
        );
        if (!$ok) { return false; }
        // Fetch the new id
        $this->tasktable->query("SELECT LAST_INSERT_ID() AS id", $rows, $rn);
        $task_id = (int)($rows[0]['id'] ?? 0);
        return ['task_id' => $task_id, 'task_name' => $name];
    }

    public function removeTask($d, &$errormessage = '') {
        $task_id = (int)($d['task_id'] ?? 0);
        if (!$task_id) { $errormessage = 'task_id required.'; return false; }
        return $this->tasktable->delete("id = {$task_id}", $numrows, false);
    }

    // -------------------------------------------------------------------------
    // Step 3: Roles per Task
    // -------------------------------------------------------------------------
    public function getAllRoles(&$roles) {
        return $this->roletable->selectall($roles, $numrows, 'name');
    }

    public function createRole($d, &$errormessage = '') {
        $name = trim($d['name'] ?? '');
        if ($name === '') { $errormessage = 'Role name is required.'; return false; }
        $this->roletable->clear();
        $this->roletable->setfield('name',        $name);
        $this->roletable->setfield('cellname',    trim($d['cellname'] ?? $name));
        $this->roletable->setfield('rosterindex', (int)($d['rosterindex'] ?? 0));
        $ok = $this->roletable->insert(true, $new_id, false, $errormessage);
        if (!$ok) { return false; }
        return ['role_id' => (int)$new_id, 'role_name' => $name];
    }

    public function addTaskRole($d, &$errormessage = '') {
        $task_id = (int)($d['task_id']      ?? 0);
        $role_id = (int)($d['role_id']      ?? 0);
        $min_qty = (int)($d['min_quantity'] ?? 1);
        $max_qty = (int)($d['max_quantity'] ?? 1);
        if (!$task_id || !$role_id) { $errormessage = 'Task and role required.'; return false; }

        // Prevent duplicate
        $this->taskroletable->query(
            "SELECT id FROM task_role WHERE task_id = {$task_id} AND role_id = {$role_id} LIMIT 1",
            $existing, $count
        );
        if ($count > 0) {
            $task_role_id = (int)$existing[0]['id'];
        } else {
            $this->taskroletable->clear();
            $this->taskroletable->setfield('task_id',      $task_id);
            $this->taskroletable->setfield('role_id',      $role_id);
            $this->taskroletable->setfield('min_quantity', $min_qty);
            $this->taskroletable->setfield('max_quantity', $max_qty);
            $ok = $this->taskroletable->insert(true, $new_id, false, $errormessage);
            if (!$ok) { return false; }
            $task_role_id = (int)$new_id;
        }
        $this->roletable->query("SELECT name FROM role WHERE id = {$role_id}", $rr, $rn);
        return [
            'task_role_id' => $task_role_id,
            'role_id'      => $role_id,
            'role_name'    => $rr[0]['name'] ?? '',
            'min_quantity' => $min_qty,
            'max_quantity' => $max_qty,
        ];
    }

    public function removeTaskRole($d, &$errormessage = '') {
        $task_role_id = (int)($d['task_role_id'] ?? 0);
        if (!$task_role_id) { $errormessage = 'task_role_id required.'; return false; }
        return $this->taskroletable->delete("id = {$task_role_id}", $numrows, false);
    }

    // -------------------------------------------------------------------------
    // Step 4: Booking Alerts
    // -------------------------------------------------------------------------
    public function saveAlert($d, &$errormessage = '') {
        $task_role_id = (int)($d['task_role_id'] ?? 0);
        $period       = (int)($d['period']       ?? 0);
        $level        = (int)($d['level']        ?? 0);
        if (!$task_role_id || !$period || !$level) {
            $errormessage = 'task_role_id, period, and level are required.';
            return false;
        }
        $this->rosteralerttable->clear();
        $this->rosteralerttable->setfield('task_role_id', $task_role_id);
        $this->rosteralerttable->setfield('period',       $period);
        $this->rosteralerttable->setfield('level',        $level);
        $ok = $this->rosteralerttable->insert(true, $new_id, false, $errormessage);
        if (!$ok) { return false; }
        return ['alert_id' => (int)$new_id];
    }

    public function removeAlert($d, &$errormessage = '') {
        $alert_id = (int)($d['alert_id'] ?? 0);
        if (!$alert_id) { $errormessage = 'alert_id required.'; return false; }
        return $this->rosteralerttable->delete("id = {$alert_id}", $numrows, false);
    }

    // -------------------------------------------------------------------------
    // Step 5: User-Role Assignment
    // -------------------------------------------------------------------------
    public function assignUserRole($d, &$errormessage = '') {
        $user_id = (int)($d['user_id'] ?? 0);
        $role_id = (int)($d['role_id'] ?? 0);
        if (!$user_id || !$role_id) { $errormessage = 'user_id and role_id required.'; return false; }
        // Prevent duplicate
        $this->userroletable->query(
            "SELECT id FROM user_role WHERE user_id = {$user_id} AND role_id = {$role_id} LIMIT 1",
            $existing, $count
        );
        if ($count > 0) { return ['user_role_id' => (int)$existing[0]['id']]; }
        $this->userroletable->clear();
        $this->userroletable->setfield('user_id', $user_id);
        $this->userroletable->setfield('role_id', $role_id);
        $ok = $this->userroletable->insert(true, $new_id, false, $errormessage);
        if (!$ok) { return false; }
        return ['user_role_id' => (int)$new_id];
    }

    public function removeUserRole($d, &$errormessage = '') {
        $user_id = (int)($d['user_id'] ?? 0);
        $role_id = (int)($d['role_id'] ?? 0);
        if (!$user_id || !$role_id) { $errormessage = 'user_id and role_id required.'; return false; }
        return $this->userroletable->delete("user_id = {$user_id} AND role_id = {$role_id}", $numrows, false);
    }

    // -------------------------------------------------------------------------
    // Step 6: Build Sessions
    // -------------------------------------------------------------------------
    public function buildSessions($d, &$errormessage = '', $taskextendermanager = null) {
        $roster_id = (int)($d['roster_id'] ?? 0);
        if (!$roster_id) { $errormessage = 'roster_id required.'; return false; }
        $this->table->query("SELECT id FROM task WHERE page_id = {$roster_id}", $tasks, $numrows);
        $built = 0;
        foreach ($tasks as $task) {
            try {
                $taskextendermanager->extendsessions((int)$task['id'], null, 'wizard', false);
                $built++;
            } catch (\Throwable $e) {
                // Sessions are created before any logging call; catch logging failures silently
            }
        }
        return ['tasks_processed' => $built];
    }

    // -------------------------------------------------------------------------
    // Full roster data: for loading an existing roster into the wizard
    // -------------------------------------------------------------------------
    public function getFullData($roster_id, &$errormessage = '') {
        $roster_id = (int)$roster_id;
        if (!$roster_id) { $errormessage = 'roster_id required.'; return false; }

        $this->table->query(
            "SELECT r.*, p.pagenumber FROM roster r JOIN page p ON p.id = r.id WHERE r.id = {$roster_id} LIMIT 1",
            $rosterRows, $rn
        );
        if (!$rn) { $errormessage = 'Roster not found.'; return false; }
        $roster = $rosterRows[0];

        $this->table->query(
            "SELECT * FROM task WHERE page_id = {$roster_id} ORDER BY taskgroup, groupindex, id",
            $tasks, $taskCount
        );

        $taskRolesByTask = [];
        $alertsByTaskRole = [];

        if ($taskCount > 0) {
            $taskIds = implode(',', array_map('intval', array_column($tasks, 'id')));
            $this->taskroletable->query(
                "SELECT tr.*, r.name AS role_name FROM task_role tr JOIN role r ON r.id = tr.role_id WHERE tr.task_id IN ({$taskIds})",
                $trRows, $trn
            );
            foreach ($trRows as $tr) {
                $taskRolesByTask[$tr['task_id']][] = $tr;
            }
            if ($trn > 0) {
                $trIds = implode(',', array_map('intval', array_column($trRows, 'id')));
                $this->rosteralerttable->query(
                    "SELECT * FROM roster_alert WHERE task_role_id IN ({$trIds})",
                    $alertRows, $an
                );
                foreach ($alertRows as $a) {
                    $alertsByTaskRole[$a['task_role_id']][] = $a;
                }
            }
        }

        $structuredTasks = [];
        foreach ($tasks as $task) {
            $tid = $task['id'];
            $roles = [];
            foreach ($taskRolesByTask[$tid] ?? [] as $tr) {
                $trid = $tr['id'];
                $trAlerts = [];
                foreach ($alertsByTaskRole[$trid] ?? [] as $a) {
                    $trAlerts[] = ['id' => (int)$a['id'], 'period' => (int)$a['period'], 'level' => (int)$a['level']];
                }
                $roles[] = [
                    'id'           => (int)$trid,
                    'role_id'      => (int)$tr['role_id'],
                    'role_name'    => $tr['role_name'],
                    'min_quantity' => (int)$tr['min_quantity'],
                    'max_quantity' => (int)$tr['max_quantity'],
                    'alerts'       => $trAlerts,
                ];
            }
            $structuredTasks[] = [
                'id'         => (int)$tid,
                'name'       => $task['name'],
                'recurrence' => $task['recurrence'],
                'roles'      => $roles,
            ];
        }

        return [
            'roster' => [
                'id'                => (int)$roster['id'],
                'name'              => $roster['name'],
                'page_number'       => (int)$roster['pagenumber'],
                'maxcolumns'        => $roster['maxcolumns'],
                'sessiondepth'      => $roster['sessiondepth'],
                'leadtime'          => $roster['leadtime'],
                'publishedleadtime' => $roster['publishedleadtime'],
                'autoextendtasks'   => (int)$roster['autoextendtasks'],
                'startdate'         => $roster['startdate'] ?? '',
                'enddate'           => $roster['enddate']   ?? '',
            ],
            'tasks' => $structuredTasks,
        ];
    }

    // -------------------------------------------------------------------------
    // Init data: roles + users + existing user_roles for all roles in this roster
    // -------------------------------------------------------------------------
    public function getInitData($roster_id, &$errormessage = '') {
        $this->roletable->selectall($all_roles, $rn, 'name');
        $this->table->query(
            "SELECT id, CONCAT(given_name, ' ', family_name) AS name FROM user ORDER BY given_name, family_name",
            $all_users, $un
        );
        // Existing user_role assignments for all roles
        $all_user_roles = [];
        if (!empty($all_roles)) {
            $ids = implode(',', array_map('intval', array_column($all_roles, 'id')));
            $this->userroletable->query(
                "SELECT user_id, role_id FROM user_role WHERE role_id IN ({$ids})",
                $all_user_roles, $urn
            );
        }
        return [
            'all_roles'      => $all_roles      ?? [],
            'all_users'      => $all_users      ?? [],
            'all_user_roles' => $all_user_roles ?? [],
        ];
    }
}
