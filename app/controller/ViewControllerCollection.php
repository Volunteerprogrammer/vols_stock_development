<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ViewControllerCollection {
    private $trace = false;
	public function __construct(
                                   protected UserPageController $userpagecontroller

                                // protected LoginForm $loginform
                                // ,protected ConfigForm $configform
                                // ,protected RosterForm $rosterform
                                // ,protected UserProfileForm $userprofileform
                                // ,protected TaskForm $taskform
                                // ,protected RoleForm $roleform
                                // ,protected ClientAdminForm $clientadminform
                                // ,protected ClientVolsForm $clientvolsform
                                // ,protected ActionForm $actionform
                                // ,protected PageForm $pageform
                                // ,protected SessionForm $sessionform
                                // ,protected AttendanceAdminForm $attendanceadminform
                                // ,protected AttendanceVolsForm $attendancevolsform
                                // ,protected AttendanceReportForm $attendancereportform
                                // ,protected StartNewPasswordForm $startnewpasswordform
                                // ,protected EnterNewPasswordForm $enternewpasswordform
                                // ,protected MenuitemForm $menuitemform
                                // ,protected ConfirmCodeForm $ConfirmCodeForm
                                // ,protected SessionListForm $SessionList
                            ){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
	}
    public function UserPageController() {
        return $this->userpagecontroller;
    }
    // public function LoginForm() {
    //     return $this->loginform;
    // }
    // public function ConfigForm() {
    //     return $this->configform;
    // }
    // public function MenuitemForm() {
    //     return $this->menuitemform;
    // }
    // public function StartNewPasswordForm() {
    //     return $this->startnewpasswordform;
    // }
    // public function EnterNewPasswordForm() {
    //     return $this->enternewpasswordform;
    // }
    // public function ConfirmCodeForm() {
    //     return $this->ConfirmCodeForm;
    // }
    // public function RosterForm() {
    //     return $this->rosterform;
    // }
    // public function UserProfileForm() {
    //     return $this->userprofileform;
    // }
    // public function ClientAdminForm() {
    //     return $this->clientadminform;
    // }
    // public function ClientVolsForm() {
    //     return $this->clientvolsform;
    // }
    // public function TaskForm() {
    //     return $this->taskform;
    // }
    // public function RoleForm() {
    //     return $this->roleform;
    // }
    //  public function ActionForm() {
    //     return $this->actionform;
    // }
    //  public function PageForm() {
    //     return $this->pageform;
    // }
    // public function AttendanceAdminForm() {
    //     return $this->attendanceadminform;
    // }
    // public function AttendanceVolsForm() {
    //     return $this->attendancevolsform;
    // }
    //  public function AttendanceReportForm() {
    //     return $this->attendancereportform;
    // }
    // public function SessionForm() {
    //     return $this->sessionform;
    // }
}