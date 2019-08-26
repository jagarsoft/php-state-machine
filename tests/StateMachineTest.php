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

    function test_can_make_StateMachine_from_construct() {
        $sm = new StateMachine([
            StateEnum::ESTADO_1 => [ EventEnum::EVENTO_A => [ StateEnum::ESTADO_2]  ],
            StateEnum::ESTADO_2 => [ EventEnum::EVENTO_B => [ StateEnum::ESTADO_3]  ],
            StateEnum::ESTADO_3 => [ EventEnum::EVENTO_C => [ StateEnum::ESTADO_1]  ],
        ]);

        $this->assertArraySubsetBis([
            StateEnum::ESTADO_1 => [ EventEnum::EVENTO_A => [ StateEnum::ESTADO_2, null ]  ],
            StateEnum::ESTADO_2 => [ EventEnum::EVENTO_B => [ StateEnum::ESTADO_3, null ]  ],
            StateEnum::ESTADO_3 => [ EventEnum::EVENTO_C => [ StateEnum::ESTADO_1, null ]  ],
        ], $sm->getMachineToArray());

        /*$this->markTestIncomplete(
            'Verify Machine\'s structure is pending.'
        );*/

        $sm->dumpStates();
        $sm->dumpEvents();

    }

    function test_can_make_transition() {
        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        $sm->addState($estado_1);
        $sm->addTransition($estado_1, $evento_a, $estado_1, null);

        $this->assertTrue(TRUE);

        $this->markTestIncomplete(
            'Verify Transition\'s structure is pending.'
        );
    }

    function test_can_do_transition() {
        $fired = false;

        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        //$sm->addState($estado_1);
        $sm->addTransition($estado_1, $evento_a, $estado_1, function() use (&$fired){
                $fired = true;
        });

        $sm->fireEvent($evento_a);

        $this->assertTrue($fired);
    }

    function test_can_do_transitions_from_the_same_state() {
        $fired[EventEnum::EVENTO_A] = false;
        $fired[EventEnum::EVENTO_B] = false;

        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);
        $evento_b = new Event(EventEnum::EVENTO_B);

        $sm = new StateMachine();

        $sm->addTransition($estado_1, $evento_a, $estado_1, function() use (&$fired){
            $fired[EventEnum::EVENTO_A] = true;
        });

        $sm->addTransition($estado_1, $evento_b, $estado_1, function() use (&$fired){
            $fired[EventEnum::EVENTO_B] = true;
        });

        $sm->fireEvent($evento_a);
        $sm->fireEvent($evento_b);

        $this->assertTrue($fired[EventEnum::EVENTO_A], "From State 1 with Event A to new State 1");
        $this->assertTrue($fired[EventEnum::EVENTO_B], "From State 1 with Event B to new State 1");
    }

    function test_can_do_transitions_for_the_same_events() {
        $fired[StateEnum::ESTADO_1] = false;
        $fired[StateEnum::ESTADO_2] = false;

        $estado_1 = new State(StateEnum::ESTADO_1);
        $estado_2 = new State(StateEnum::ESTADO_2);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        $sm->addTransition($estado_1, $evento_a, $estado_2, function() use (&$fired){
            $fired[StateEnum::ESTADO_1] = true;

        });

        $sm->addTransition($estado_2, $evento_a, $estado_1, function() use (&$fired){
            $fired[StateEnum::ESTADO_2] = true;
        });

        $sm->fireEvent($evento_a);
        $sm->fireEvent($evento_a);

        $this->assertTrue($fired[StateEnum::ESTADO_1], "From State 1 with Event A to new State 2");
        $this->assertTrue($fired[StateEnum::ESTADO_2], "From State 2 with Event A to new State 1");
    }

    function test_can_do_transition_with_null_action() {
        $estado_1 = new State(StateEnum::ESTADO_1);
        $evento_a = new Event(EventEnum::EVENTO_A);

        $sm = new StateMachine();

        //$sm->addState($estado_1);
        $sm->addTransition($estado_1, $evento_a, $estado_1, null);

        $sm->fireEvent($evento_a);

        $this->assertTrue(TRUE);
    }

	function skip_test_can_use_StateMachine() {
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


    /**
     * Asserts that an array has a specified subset.
     *
     * This method was taken over from PHPUnit where it was deprecated. See link for more info.
     *
     * @param  array|\ArrayAccess  $subset
     * @param  array|\ArrayAccess  $array
     * @param  bool  $checkForObjectIdentity
     * @param  string  $message
     * @return void
     *
     * @link https://github.com/sebastianbergmann/phpunit/issues/3494
     * @link https://github.com/laravel/framework/pull/27441/commits/695a29928d5f3e595363306cf62ba4ff653d73ba
     */
    public static function assertArraySubsetBis($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        if (! (is_array($subset) || $subset instanceof ArrayAccess)) {
            throw PHPUnit\Util\InvalidArgumentHelper::factory(1, 'array or ArrayAccess');
        }
        if (! (is_array($array) || $array instanceof ArrayAccess)) {
            throw PHPUnit\Util\InvalidArgumentHelper::factory(2, 'array or ArrayAccess');
        }
        $constraint = new PHPUnit\Framework\Constraint\ArraySubset($subset, $checkForObjectIdentity);
        static::assertThat($array, $constraint, $message);
    }
}
