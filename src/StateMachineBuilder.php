<?php

namespace jagarsoft\StateMachine;

interface StateMachineBuilder
{
    /**
     * return [
     *      StateEnum::STATE_1 => [EventEnum::EVENT_A => [StateEnum::STATE_2]],
     *      StateEnum::STATE_2 => [EventEnum::EVENT_B => [StateEnum::STATE_3]],
     *      StateEnum::STATE_3 => [EventEnum::EVENT_C => [StateEnum::STATE_1]],
     * ];
     *
     * @return array[][]
     */
    public function from();

    /**
     * sm param is an array[][] which you can export accordingly
     *
     * @param array[][]
     */
    public function to($sm);
}
