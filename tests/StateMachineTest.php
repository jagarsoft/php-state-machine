<?php

use PHPUnit\Framework\TestCase;
use jagarsoft\StateMachine\StateMachine;
use jagarsoft\StateMachine\Stubs\StateEnum;
use jagarsoft\StateMachine\Stubs\EventEnum;

class StateMachineTest extends TestCase {
	
	/**
	* @link https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-67239415
	*/
	function test_can_make_StateMachine_class(){
		try {
		  $sm = new StateMachine();
		} catch (\Exception $notExpected) {
		  $this->fail();
		}

		$this->assertTrue(TRUE);
	}

    function test_can_make_StateMachine_from_construct() {
        $sm = new StateMachine([
            StateEnum::STATE_1 => [ EventEnum::EVENT_A => [ StateEnum::STATE_2]  ],
            StateEnum::STATE_2 => [ EventEnum::EVENT_B => [ StateEnum::STATE_3]  ],
            StateEnum::STATE_3 => [ EventEnum::EVENT_C => [ StateEnum::STATE_1]  ],
        ]);

        $this->assertArraySubsetBis([
            StateEnum::STATE_1 => [ EventEnum::EVENT_A => [ StateEnum::STATE_2, null ]  ],
            StateEnum::STATE_2 => [ EventEnum::EVENT_B => [ StateEnum::STATE_3, null ]  ],
            StateEnum::STATE_3 => [ EventEnum::EVENT_C => [ StateEnum::STATE_1, null ]  ],
        ], $sm->getMachineToArray());

        //$sm->dumpStates();
        //$sm->dumpEvents();
    }

    function test_states_and_events_can_not_be_null(){
	    $this->expectException(InvalidArgumentException::class);

        $sm = new StateMachine();

        $sm->addState(null); // Not NULL

        $sm->addState(""); // Not Blank

        $sm->addTransition(null, null, null); // Not from NULL state

        $sm->addTransition("", null, null); // Not from Blank state

        $sm->addTransition(1, null, null); // Not for NULL event

        $sm->addTransition(1, "", null);// Not for Blank event

        $sm->addTransition(1, 2, null); // Not to NULL next state

        $sm->addTransition(1, 2, ""); // Not to Blank next state

        $this->assertTrue(TRUE);

        //$sm->dumpStates();
        //$sm->dumpEvents();
    }

    function test_can_set_currentState_from_first_addState_or_addTransition(){
        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;

        $sm_A = new StateMachine();

        $sm_A->addState($state_1);
        $this->assertSame($state_1, $sm_A->getCurrentState());

        $sm_B = new StateMachine();

        $sm_B->addTransition($state_1, $event_a, $state_1, null);
        $this->assertSame($state_1, $sm_B->getCurrentState());
    }

    function test_can_make_valid_transition() {
        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addTransition($state_1, $event_a, $state_1, null);

        $this->assertTrue(TRUE);
    }

    function test_can_do_valid_transition() {
        $fired = false;

        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        //$sm->addState($state_1);
        $sm->addTransition($state_1, $event_a, $state_1, function() use (&$fired){
                $fired = true;
        });

        $sm->fireEvent($event_a);

        $this->assertTrue($fired);
    }

    function test_can_not_do_transition_to_undefined_state() {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        //$sm->addState($state_1);
        $sm->addTransition($state_1, $event_a, $state_2, null);

        $sm->fireEvent($event_a);
        $sm->fireEvent($event_a); // This event will fire an unexpected $state_2 state

        $this->assertTrue(TRUE);
    }

    function test_can_not_do_transition_for_undefined_event() {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;

        $sm = new StateMachine();

        //$sm->addState($state_1);
        $sm->addTransition($state_1, $event_a, $state_1, null);

        $sm->fireEvent($event_b); // Unexpected event event_b on current state

        $this->assertTrue(TRUE);
    }

    function test_can_do_transitions_from_the_same_state() {
        $fired[EventEnum::EVENT_A] = false;
        $fired[EventEnum::EVENT_B] = false;

        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1, function() use (&$fired){
            $fired[EventEnum::EVENT_A] = true;
        });

        $sm->addTransition($state_1, $event_b, $state_1, function() use (&$fired){
            $fired[EventEnum::EVENT_B] = true;
        });

        $sm->fireEvent($event_a);
        $sm->fireEvent($event_b);

        $this->assertTrue($fired[EventEnum::EVENT_A], "From 1 State on Event A to new 1 State");
        $this->assertTrue($fired[EventEnum::EVENT_B], "From 1 State on Event B to new 1 State");
    }

    function test_can_do_transitions_for_the_same_events() {
        $fired[StateEnum::STATE_1] = false;
        $fired[StateEnum::STATE_2] = false;

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_2, function() use (&$fired){
            $fired[StateEnum::STATE_1] = true;

        });

        $sm->addTransition($state_2, $event_a, $state_1, function() use (&$fired){
            $fired[StateEnum::STATE_2] = true;
        });

        $sm->fireEvent($event_a);
        $sm->fireEvent($event_a);

        $this->assertTrue($fired[StateEnum::STATE_1], "From 1 State on Event A to new 2 State");
        $this->assertTrue($fired[StateEnum::STATE_2], "From 2 State on Event A to new 1 State");
    }

    function test_can_do_transition_with_null_action() {
        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        //$sm->addState($state_1);
        $sm->addTransition($state_1, $event_a, $state_1, null);

        $sm->fireEvent($event_a);

        $this->assertTrue(TRUE);
    }

    public function test_can_detect_undefined_states_and_events(){
        $state_1 = StateEnum::STATE_1;
        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1+1, $event_a, $state_1, null);
    }



	function test_can_use_method_chaining() {
        $that = $this;

		$state_1 = StateEnum::STATE_1;
		$state_2 = StateEnum::STATE_2;
		$state_3 = StateEnum::STATE_3;

		$event_a = EventEnum::EVENT_A;
		$event_b = EventEnum::EVENT_B;
		$event_c = EventEnum::EVENT_C;

        echo PHP_EOL;
        $commonAction = function (StateMachine $sm){
            echo "My current state is {$sm->getCurrentState()}".
                 " on {$sm->getCurrentEvent()}".
                 " and {$sm->getNextState()} will be the next state".PHP_EOL;
        };

        ($sm = new StateMachine())
                ->addState($state_1)
                ->addState($state_2)
                ->addState($state_3)

                ->addTransition($state_1, $event_a, $state_2, $commonAction)
                ->addTransition($state_2, $event_b, $state_3, $commonAction)
                ->addTransition($state_3, $event_c, $state_1, $commonAction)

                ->fireEvent($event_a)
                ->fireEvent($event_b)
                ->fireEvent($event_c)

                ->fireEvent($event_a)
                ->fireEvent($event_b)
                ->fireEvent($event_c);

		$this->assertTrue(TRUE);
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
