<?php
namespace app\view\body;
use \lib\StdLib as lib;
class BodyCollection {
    private $trace = false;
	public function __construct(protected LoginBody $loginBody
                                ,protected StandardBody $standardbody
//                                ,protected UserSelectBody $userselectBody,
//                                ,protected UserBody $userBody,
//                                ,protected SessionListBody $SessionList
                               ){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>\n"; }
	}
    public function LoginBody() {
        return $this->loginBody;
    }
    public function StandardBody() {
        return $this->standardbody;
    }
    // public function UserSelectBody() {
    //     return $this->UserSelectBody;
    // }
    // public function UserBody() {
    //     return $this->UserBody;
    // }
    // public function SessionListBody() {
    //     return $this->SessionListBody;
    // }
}