<?php

namespace jagarsoft\StateMachine\Stubs;

use jagarsoft\StateMachine\Stubs\StateEnum;
use jagarsoft\StateMachine\Stubs\EventEnum;

class StateMachineBuilder {
    public function __invoke()
    {
        return [
            StateEnum::STATE_1 => [EventEnum::EVENT_A => [StateEnum::STATE_2]],
            StateEnum::STATE_2 => [EventEnum::EVENT_B => [StateEnum::STATE_3]],
            StateEnum::STATE_3 => [EventEnum::EVENT_C => [StateEnum::STATE_1]],
        ];
    }
}