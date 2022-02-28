<?php

namespace jagarsoft\StateMachine\Stubs;

use jagarsoft\StateMachine\StateMachine;
use jagarsoft\StateMachine\Stubs\StateEnum;
use jagarsoft\StateMachine\Stubs\EventEnum;
use jagarsoft\StateMachine\StateMachineBuilder;

class StateMachineArrayBuilder implements StateMachineBuilder {
    /**
     * @return array[][]
     */
    public function from()
    {
        /*
                      EVENT_A | EVENT_B | EVENT_C
                    +---------+---------+---------+
            STATE_1 | before  |         |         |
                    | STATE_2 |         |         |
            --------+---------+---------+---------+
            STATE_2 |         | guard   |         |
                    |         | STATE_3 |         |
            --------+---------+---------+---------+
            STATE_3 |         |         | STATE_1 |
                    |         |         | action  |
            --------+-----------------------------+
            STATE_4 |         |         | STATE_1 |
                    |         |         | after   |
            --------+-----------------------------+
         */
        return [
        StateEnum::STATE_1 => [ EventEnum::EVENT_A => [ StateEnum::STATE_2 /*, StateMachine::$EXEC_BEFORE => function(){}*/ ] ],
        StateEnum::STATE_2 => [ EventEnum::EVENT_B => [ StateEnum::STATE_3 /*, StateMachine::$EXEC_GUARD => function(){return false}*/] ],
        StateEnum::STATE_3 => [ EventEnum::EVENT_C => [ StateEnum::STATE_1 /*, StateMachine::$EXEC_ACTION => function(){}*/ ] ]
        //StateEnum::STATE_4 => [ EventEnum::EVENT_C => [ StateEnum::STATE_1 /*, StateMachine::$EXEC_AFTER => function(){}*/ ] ],
        ];
    }

    /**
     * @param array[][]
     */
    public function to($sm)
    {
        // TODO: Implement to() method.
    }
}
