<?php
namespace app\view\body;
use \lib\StdLib as lib;
class BodyCollection {
    private $trace = false;
    private array $instances = [];

    public function __construct(private \fw\factory\ClassFactory $factory) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>\n"; }
    }

    public function LoginBody(): LoginBody {
        return $this->instances[LoginBody::class] ??= $this->factory->getClass(LoginBody::class);
    }
    public function StandardBody(): StandardBody {
        return $this->instances[StandardBody::class] ??= $this->factory->getClass(StandardBody::class);
    }
}
