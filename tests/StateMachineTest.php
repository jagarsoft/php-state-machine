<?php

use PHPUnit\Framework\TestCase;
use jagarsoft\StateMachine\StateMachine;
use jagarsoft\StateMachine\State;
use jagarsoft\StateMachine\Event;
use jagarsoft\StateMachine\Stubs\StateEnum;
use jagarsoft\StateMachine\Stubs\EventEnum;

class StateMachineTest extends TestCase {
	
	/**
	* @link https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-67239415
	*/
	function test_can_make_StateMachine_class(){
		try {
		  $sm = new StateMachine();
		} catch (InvalidArgumentException $notExpected) {
		  $this->fail();
		}

		$this->assertTrue(TRUE);
	}

    function test_can_make_State() {
        $estado_1 = new State(StateEnum::ESTADO_1);

        $this->assertSame(StateEnum::ESTADO_1, $estado_1->getState());
    }

    function test_can_make_Event() {
        $evento_a = new Event(EventEnum::EVENTO_A);

        $this->assertSame(EventEnum::EVENTO_A, $evento_a->getEvent());
    }

    function skip_test_can_make_Transition() {
        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        $sm->addState($estado_1);
        $sm->addTransition($estado_1, $evento_a, $estado_1, null);

        $sm->dumpStates();
        $sm->dumpEvents();
    }

    function test_can_do_Transition() {
        $that = $this;

        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        //$sm->addState($estado_1);
        $sm->addTransition($estado_1, $evento_a, $estado_1, function() use ($that){
                $that->assertTrue(true);
        });

        $sm->fireEvent($estado_1, $evento_a);

        $sm->dumpStates();
        $sm->dumpEvents();
    }

	function skiip_test_can_use_StateMachine() {
		$estado_1 = new State(StateEnum::ESTADO_1);
		$estado_2 = new State(StateEnum::ESTADO_2);
		$estado_3 = new State(StateEnum::ESTADO_3);

		$evento_a = new Event(EventEnum::EVENTO_A);
		$evento_b = new Event(EventEnum::EVENTO_B);
		$evento_c = new Event(EventEnum::EVENTO_C);

		$sm = new StateMachine();

		$sm->addState($estado_1);
		$sm->addState($estado_2);
		$sm->addState($estado_3);

		$sm->addTransition($estado_1, $evento_a, $estado_2, null);
		$sm->addTransition($estado_2, $evento_b, $estado_3, null);
		$sm->addTransition($estado_3, $evento_c, $estado_1, null);

		$sm->dumpStates();
		$sm->dumpEvents();
	}
}
