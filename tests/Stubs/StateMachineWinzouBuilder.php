<?php

namespace jagarsoft\StateMachine\Stubs;

use jagarsoft\StateMachine\StateMachine;
use jagarsoft\StateMachine\StateMachineBuilder;

class StateMachineWinzouBuilder implements StateMachineBuilder {
    /**
     * @return array[][]
     */
    public function from()
    {
        $sm_array = [];

        /**
         * @link https://github.com/winzou/state-machine
         */
        $config = array(
            'graph'         => 'myGraphA', // Name of the current graph - there can be many of them attached to the same object
            'property_path' => 'stateA',  // Property path of the object actually holding the state
            'states'        => array(
                'checkout',
                'pending',
                'confirmed',
                'cancelled'
            ),
            'transitions' => array(
                'create' => array(
                    'from' => array('checkout'),
                    'to'   => 'pending'
                ),
                'confirm' => array(
                    'from' => array('checkout', 'pending'),
                    'to'   => 'confirmed'
                ),
                'cancel' => array(
                    'from' => array('confirmed'),
                    'to'   => 'cancelled'
                )
            ),
            'callbacks' => array(
                'guard' => array(
                    'guard-cancel' => array(
                        'to' => array('cancelled'), // Will be called only for transitions going to this state
                        'do' => function() { echo('guarding to cancelled state'); return false; }
                    )
                ),
                'before' => array(
                    'from-checkout' => array(
                        'from' => array('checkout'), // Will be called only for transitions coming from this state
                        'do'   => function() { echo('from "checkout" state, before transition'); }
                    )
                ),
                'after' => array(
                    'on-confirm' => array(
                        'on' => array('confirm'), // Will be called only on this transition
                        'do' => function() { echo('on "confirm" state, after transition'); }
                    ),
                    'to-cancelled' => array(
                        'to' => array('cancelled'), // Will be called only for transitions going to this state
                        'do' => function() { echo('after transition to "cancelled" state'); }
                    ),
                    'cancel-date' => array(
                        'to' => array('cancelled'),
                        'do' => array('object', 'setCancelled'), // Pending of implementation
                    ),
                )
            )
        );

        $guard_to = $config['callbacks']['guard']['guard-cancel']['to'];
        $guard = $config['callbacks']['guard']['guard-cancel']['do'];
        $before_from = $config['callbacks']['before']['from-checkout']['from'];
        $before = $config['callbacks']['before']['from-checkout']['do'];
        $after_on = $config['callbacks']['after']['on-confirm']['on'];
        $after = $config['callbacks']['after']['on-confirm']['do'];
        //$after_cancelled_to = $config['callbacks']['after']['to-cancelled']['to'];
        //$after_cancel_date_to = $config['callbacks']['after']['cancel-date']['to']; // Pending of implementation

        $states = $config['states'];
        foreach ($states as $state){
            $sm_array[$state] = [];
        }

        $transitions = $config['transitions'];
        foreach ($transitions as $event => $transition){
            $from = $transition['from'];
            foreach ($from as $state){

                $sm_array[$state][$event] = [ StateMachine::NEXT_STATE => $transition['to'] ];

                if( in_array($transition['to'], $guard_to) )
                    $sm_array[$state][$event] = [ StateMachine::NEXT_STATE => $transition['to'], StateMachine::EXEC_GUARD => $guard ];
                if( in_array($state, $before_from) )
                    $sm_array[$state][$event] = [ StateMachine::NEXT_STATE => $transition['to'], StateMachine::EXEC_BEFORE => $before ];
                if( in_array($transition['to'], $after_on) )
                    $sm_array[$state][$event] = [ StateMachine::NEXT_STATE => $transition['to'], Statemachine::EXEC_AFTER => $after ];
            }
        }

        return $sm_array;
    }

    /**
     * @param array[][]
     */
    public function to($sm)
    {
    }
}
