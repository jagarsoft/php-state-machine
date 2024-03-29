<?php

use PHPUnit\Framework\TestCase;
use jagarsoft\StateMachine\StateMachine;
use jagarsoft\StateMachine\Stubs\StateEnum;
use jagarsoft\StateMachine\Stubs\EventEnum;
use jagarsoft\StateMachine\Stubs\StateMachineArrayBuilder;
use jagarsoft\StateMachine\Stubs\StateMachineWinzouBuilder;

class StateMachineTest extends TestCase
{
    /**
    * @link https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-67239415
    */
    public function test_can_make_StateMachine_class()
    {
        try {
          new StateMachine();
        } catch (\Exception $notExpected) {
          $this->fail();
        }

        $this->assertTrue(TRUE);
    }

    public function test_can_make_StateMachine_from_StateMachineArrayBuilder()
    {
        $sm = new StateMachine(new StateMachineArrayBuilder());

        $this->assertArraySubsetBis([
            StateEnum::STATE_1 => [ EventEnum::EVENT_A => [ StateEnum::STATE_2 ] ],
            StateEnum::STATE_2 => [ EventEnum::EVENT_B => [ StateEnum::STATE_3 ] ],
            StateEnum::STATE_3 => [ EventEnum::EVENT_C => [ StateEnum::STATE_1 ] ],
        ], $sm->getMachineToArray());
    }


    public function test_can_make_StateMachine_from_StateMachineWinzouBuilder()
    {
        $sm = new StateMachine(new StateMachineWinzouBuilder());

        $this->assertArraySubsetBis([
            'checkout' =>   [ 'create' => ['pending'], 'confirm' => ['confirmed'] ],
            'pending' =>    [ 'confirm' => ['confirmed'] ],
            'confirmed' =>  [ 'cancel' => ['cancelled'] ],
            'cancelled' =>  [],
        ], $sm->getMachineToArray());

        // Current state is checkout
        $this->assertSame('checkout', $sm->getCurrentState());

        // Return true, we can apply this transition
        $this->assertTrue($sm->can('create'));

        // Return true, this transitions is applied
        // In addition, callback 'from-checkout' is called
        $sm->fireEvent('create');

        // Current state is pending
        $this->assertSame('pending', $sm->getCurrentState());

        // All possible transitions for pending state are just "confirm"
        //var_dump($stateMachine->getPossibleTransitions());

        // Return false, this transition is not applied
        // 2nd argument is soft mode: it returns false instead of throwing an exception
        //var_dump($stateMachine->apply('cancel', true));

        // Current state is still pending
        $this->assertSame('pending', $sm->getCurrentState());

        // Return true, this transition is applied
        // In addition, callback 'on-confirm' is called
        // And callback 'confirm-date' calls the method 'setConfirmedNow' on the object itself
        $sm->fireEvent('confirm');

        // Current state is confirmed
        $this->assertSame('confirmed', $sm->getCurrentState());

        // Returns false, as it is guarded
        $this->assertFalse($sm->can('cancel'));

        // Current state is still confirmed
        $this->assertSame('confirmed', $sm->getCurrentState());

        echo PHP_EOL;
    }

    /**
     * @dataProvider transitionsProvider
     */
    public function test_states_and_events_can_not_be_blank_nor_null($currentState, $currentEvent, $nextState)
    {
        $this->expectException(InvalidArgumentException::class);

        $sm = new StateMachine();

        $sm->addState($currentState); // Not NULL
        $sm->addState($nextState);    // Not Blank

        $sm->addTransition($currentState, $currentEvent, $nextState);

        $sm->fireEvent($currentEvent);
    }

    public function transitionsProvider(): array
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        return array(
            array(null, null, null),           // Not from NULL state
            array("", null, null),             // Not from Blank state
            array($state_1, null, null),       // Not on NULL event
            array($state_1, "", null),         // Not on Blank event
            array($state_1, $event_a, null),   // Not to NULL next state
            array($state_1, $event_a, ""),     // Not to Blank next state
        );
    }

    public function test_can_set_currentState_from_first_addState()
    {
        $state_1 = StateEnum::STATE_1;

        $sm = new StateMachine();

        $sm->addState($state_1);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_can_set_currentState_from_first_addTransition()
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_must_set_addState_along_addCommonTransition()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addCommonTransition($event_a, $state_2);

        $sm->fireEvent($event_a);

        $this->assertSame($state_2, $sm->getCurrentState());
    }

    public function test_can_make_valid_nextstate()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1, [
            StateMachine::$NEXT_STATE => $state_2
        ]);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_can_make_valid_transition()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_2);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_can_do_valid_transition()
    {
        $fired = false;

        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1, function() use (&$fired){
                $fired = true;
        });

        $sm->fireEvent($event_a);

        $this->assertTrue($fired);
    }

    public function test_can_not_do_transition_from_undefined_state()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_2);

        $sm->fireEvent($event_a); // $state_2 can be the destination of a transition
        $sm->fireEvent($event_a); // This event will fire an unexpected $state_2 state
    }

    public function test_can_not_do_transition_on_undefined_event()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1);

        $sm->fireEvent($event_b); // Unexpected event event_b on current state
    }

    public function test_can_do_transitions_from_the_same_state()
    {
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

        $this->assertTrue((bool)$fired[EventEnum::EVENT_A], "From 1 State on Event A to new 1 State");
        $this->assertTrue((bool)$fired[EventEnum::EVENT_B], "From 1 State on Event B to new 1 State");
    }

    public function test_can_do_transitions_on_the_same_events()
    {
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

        $this->assertTrue((bool)$fired[StateEnum::STATE_1], "From 1 State on Event A to new 2 State");
        $this->assertTrue((bool)$fired[StateEnum::STATE_2], "From 2 State on Event A to new 1 State");
    }

    public function test_currentevent_is_null_without_firing()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new Statemachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addTransition($state_1, $event_a, $state_2);

        $this->assertNull($sm->getCurrentEvent());
    }

    public function test_can_do_transition_with_null_action()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addTransition($state_1, $event_a, $state_2);

        $sm->fireEvent($event_a);

        $this->assertSame($state_2, $sm->getCurrentState());
    }

    public function test_action_argument_must_be_Closure_or_array()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addTransition($state_1, $event_a, $state_2, 'This is not a valid argument');
    }

    public function test_transition_can_be_proven()
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->addTransition($state_1, $event_a, $state_1);

        $this->assertTrue($sm->can($event_a));
        $this->assertFalse($sm->can($event_b));
    }

    public function test_transition_can_not_be_proven_with_a_blank_nor_null_event()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->can("");
        $sm->can(null);
    }

    public function test_transition_can_be_proven_with_a_valid_nextstate()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->addTransition($state_1, $event_a, $state_2); // undefined next state

        $sm->can($event_a);
    }

    public function test_action_receives_the_same_machine_as_an_argument()
    {
        $that = $this;

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $actual_sm = new StateMachine();

        $actual_sm->addState($state_1);
        $actual_sm->addState($state_2);

        $actual_sm->addTransition($state_1, $event_a, $state_2,
            function (StateMachine $expected_sm) use ($that, $actual_sm) {
            $that->assertSame($expected_sm, $actual_sm);
        });

        $actual_sm->fireEvent($event_a);
    }

    public function test_can_cancel_transition()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addTransition($state_1, $event_a, $state_2, function (StateMachine $sm){
            $sm->cancelTransition();
        });

        $sm->fireEvent($event_a);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_fire_nested_events_are_enqueued()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $state_3 = StateEnum::STATE_3;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;
        $event_c = EventEnum::EVENT_C;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);
        $sm->addState($state_3);

        $sm->addTransition($state_1, $event_a, $state_2);
        $sm->addTransition($state_2, $event_b, $state_3, function (StateMachine $sm) use ($event_c){
            $sm->fireEvent($event_c);
        });
        $sm->addTransition($state_3, $event_c, $state_1);

        $sm->fireEvent($event_a);
        $sm->fireEvent($event_b); // fire event c, nested, after b

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    public function test_can_cancel_nested_transitions_in_any_moment()
    {
        $fired[EventEnum::EVENT_A] = false;
        $fired[EventEnum::EVENT_B] = false;
        $fired[EventEnum::EVENT_C] = false;

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $state_3 = StateEnum::STATE_3;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;
        $event_c = EventEnum::EVENT_C;

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);
        $sm->addState($state_3);

        $sm->addTransition($state_1, $event_a, $state_2, function (StateMachine $sm) use ($event_b, &$fired){
            $sm->cancelTransition(); // transition 1 -> 2 is cancelled
            $sm->fireEvent($event_b);// do transition 1 -> 3 instead, enqueued
            $fired[EventEnum::EVENT_A] = true;
        });
        $sm->addTransition($state_1, $event_b, $state_3, function (StateMachine $sm) use ($event_c, &$fired){
            $sm->fireEvent($event_c);// do transition 1 -> 1 instead, enqueued
            $sm->cancelTransition(); // transition 1 -> 3 is cancelled too
            $fired[EventEnum::EVENT_B] = true;
        });
        $sm->addTransition($state_1, $event_c, $state_1, function () use (&$fired){
            $fired[EventEnum::EVENT_C] = true;
        });

        $sm->fireEvent($event_a);

        $this->assertTrue((bool)$fired[EventEnum::EVENT_A]);
        $this->assertTrue((bool)$fired[EventEnum::EVENT_B]);
        $this->assertTrue((bool)$fired[EventEnum::EVENT_C]);

        $this->assertSame($state_1, $sm->getCurrentState());
    }

    /**
     * @dataProvider initialStatesProvider
     */
    public function test_can_fire_event_from_any_state($state_1, $state_2, $state_3)
    {
        $fired[EventEnum::EVENT_A] = false;
        $fired[EventEnum::EVENT_B] = false;
        $fired[EventEnum::EVENT_C] = false;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;
        $event_c = EventEnum::EVENT_C;

        $commonAction = function (StateMachine $sm) use (&$fired){
            $sm->cancelTransition();
            $fired[$sm->getCurrentEvent()] = true;
        };

        $sm = new StateMachine();

        $sm->addState($state_1)
           ->addState($state_2)
           ->addState($state_3)
           ->addCommonTransition($event_a, $state_3, $commonAction)
           ->addCommonTransition($event_b, $state_3, $commonAction)
           ->addCommonTransition($event_c, $state_3, $commonAction);

        $sm->fireEvent($event_a);
        $this->assertTrue($fired[$sm->getCurrentEvent()]);

        $sm->fireEvent($event_b);
        $this->assertTrue($fired[$sm->getCurrentEvent()]);

        $sm->fireEvent($event_c);
        $this->assertTrue($fired[$sm->getCurrentEvent()]);
    }

    public function initialStatesProvider(): array
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $state_3 = StateEnum::STATE_3;

        return array(
            array($state_1, $state_2, $state_3),
            array($state_2, $state_3, $state_1),
            array($state_3, $state_1, $state_2),
        );
    }

	public function test_catch_null_even_in_nested_transition()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $action = function (StateMachine $sm) {
            $sm->fireEvent(null);
        };

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->addTransition($state_1, $event_a, $state_1, $action);

        $sm->fireEvent($event_a);
    }

	public function test_can_modify_machine_dynamically()
    {
        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;
        $state_3 = StateEnum::STATE_3;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;
        $event_c = EventEnum::EVENT_C;

        $fired = false;
        $action = function () use (&$fired) {
            $fired = true;
        };

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);
        $sm->addCommonTransition($event_a, $state_2);

        $sm->fireEvent($event_a); // Running ...

        $sm->addState($state_3);
        $sm->addCommonTransition($event_b, $state_3);
        $sm->addCommonTransition($event_c, $state_3, $action);

        $sm->fireEvent($event_b);
        $sm->fireEvent($event_c);

        $this->assertTrue($fired);
    }

	public function test_can_change_action_function()
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $fired_a = false;
        $action_a = function () use (&$fired_a) {
            $fired_a = true;
        };

        $fired_b = false;
        $action_b = function () use (&$fired_b) {
            $fired_b = true;
        };

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1, $action_a);

        $sm->fireEvent($event_a);

        $this->assertTrue($fired_a);
        $fired_a = false;

        $sm->addTransition($state_1, $event_a, $state_1, $action_b);

        $sm->fireEvent($event_a);

        $this->assertFalse($fired_a);
        $this->assertTrue($fired_b);
    }

	public function test_can_use_GuardDecorator()
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $firedGuard = false;
        $guard = function() use (&$firedGuard) {
            $firedGuard = true;
            return false;
        };

        $firedAction = false;
        $action = function() use (&$firedAction) {
            $firedAction = true;
        };

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->addTransition($state_1, $event_a, $state_1, [
            StateMachine::$EXEC_GUARD => $guard,
            StateMachine::$EXEC_ACTION => $action
        ]);

        $sm->fireEvent($event_a);

        $this->assertTrue($firedGuard, true);
        $this->assertFalse($firedAction, false);
    }

	public function test_can_use_BeforeAfterDecorator()
    {
        $state_1 = StateEnum::STATE_1;

        $event_a = EventEnum::EVENT_A;

        $firedBefore = false;
        $before = function() use (&$firedBefore) {
            $firedBefore = true;
        };

        $firedAfter = false;
        $after = function() use (&$firedAfter) {
            $firedAfter = true;
        };

        $sm = new StateMachine();

        $sm->addState($state_1);

        $sm->addTransition($state_1, $event_a, $state_1, [
            StateMachine::$EXEC_GUARD => null, // it's optional, really
            StateMachine::$EXEC_BEFORE => $before,
            StateMachine::$EXEC_ACTION => null, // it's optional, really
            StateMachine::$EXEC_AFTER => $after
        ]);

        $sm->fireEvent($event_a);

        $this->assertTrue($firedBefore, true);
        $this->assertTrue($firedAfter, true);
    }

    public function test_extra_data_can_be_stored_in_transition()
    {
        $that = $this;

        $fired['FIRST_STRIKE'] = false;
        $fired['SECOND_STRIKE'] = false;

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;
        $event_b = EventEnum::EVENT_B;

        $action_data = function(StateMachine $sm) use ($that, &$fired) {
            $data = $sm->getTransitionData(['DATA']);
            if (! $fired['FIRST_STRIKE']) {
                $that->assertTrue($data['DATA']['p'], 'First strike failed');
                $fired['FIRST_STRIKE'] = true;
            } elseif (! $fired['SECOND_STRIKE']) {
                $that->assertFalse($data['DATA']['p'], 'Second strike failed');
                $fired['SECOND_STRIKE'] = true;
            }

            $data['DATA']['p'] = ! $data['DATA']['p'];
            $sm->setTransitionData(['DATA' => $data['DATA']]);
            };

        $sm = new StateMachine();

        $sm->addState($state_1);
        $sm->addState($state_2);

        $sm->addTransition($state_1, $event_a, $state_2, [
            StateMachine::$EXEC_ACTION => $action_data,
            'DATA' => array('p' => true)
        ]);
        $sm->addTransition($state_2, $event_b, $state_1);

        $sm->fireEvent($event_a); // first strike
        $sm->fireEvent($event_b);
        $sm->fireEvent($event_a); // second strike

        $this->assertTrue((bool)$fired['FIRST_STRIKE']);
        $this->assertTrue((bool)$fired['SECOND_STRIKE']);
    }

    public function test_actions_can_not_be_stored_as_extra_data_in_transition()
    {
        $this->expectException(InvalidArgumentException::class);

        $state_1 = StateEnum::STATE_1;
        $state_2 = StateEnum::STATE_2;

        $event_a = EventEnum::EVENT_A;

        $action_data = function(StateMachine $sm) {
                $sm->getTransitionData([StateMachine::$EXEC_ACTION]);
                $sm->setTransitionData([StateMachine::$EXEC_ACTION => ['DATA' => array('p' => true)]]);
            };

        $sm = new StateMachine();

        $sm->addTransition($state_1, $event_a, $state_1, [
            StateMachine::$EXEC_ACTION => $action_data,
            'DATA' => array('p' => true)
        ]);

        $sm->fireEvent($event_a);
    }

    public function test_can_use_method_chaining()
    {
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

        (new StateMachine())
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
