<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\StateMachineBuilder;
use phpDocumentor\Reflection\Types\Array_;

class StateMachine {
    protected $sm = [];
    protected $currentState = null;
    protected $currentEvent = null;
    protected $nextState = null;

    protected $cancelTransition = false;
    protected $transitionInProgress = false;
    protected $eventsQueued = [];

    public const NEXT_STATE = 0;
    public const EXEC_ACTION = 'EXEC_ACTION';
    public const EXEC_GUARD = 'EXEC_GUARD';
    public const EXEC_BEFORE = 'EXEC_BEFORE';
    public const EXEC_AFTER = 'EXEC_AFTER';

    public function __construct(StateMachineBuilder $smb = null)
    {
        if(  $smb != null )
            $this->smb = $smb->from();

        if( ! empty($this->smb) ){
            // StateEnum::CURRENT_STATE => [ EventEnum::ON_EVENT => [ NEXT_STATE, ActionClosureOrFunction ] ],
            foreach ($this->smb as $state => $transition){
                $this->addState($state);
                foreach ($transition as $onEvent => $nextStateAndAction) {
                    $this->addTransition($state, $onEvent,
                        $nextStateAndAction[self::NEXT_STATE],
                        $nextStateAndAction);
                }
            }
        }
    }

	public function to(){
        // TODO: pendig of implementation
        /*
        if( $this->smb == null )
            return;

        $this->smb->to($this->getMachineToArray());
        */
    }

    public function addState($state)
    {
        $this->argumentIsValidOrFail($state);

        $this->setCurrentStateIfThisIsInitialState($state);

        $this->sm[$state] = [];

        return $this;
    }

    public function addTransition($currentState, $currentEvent, $nextState,
                                  /*\Closure|array*/ $execAction = null,
                                  \Closure $execGuard = null,
                                  \Closure $execBefore = null,
                                  \Closure $execAfter = null)
    {
        $this->argumentIsValidOrFail($currentState);
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $this->setCurrentStateIfThisIsInitialState($currentState);

        if( is_array($execAction) ){
            $this->sm[$currentState][$currentEvent] = [ self::NEXT_STATE => $nextState ];
            $arrayActions = $execAction;
            foreach ($arrayActions as $key => $value) {
                $this->sm[$currentState][$currentEvent][$key] = $value;
            }
        } else {
            $this->sm[$currentState][$currentEvent] = [
                self::NEXT_STATE => $nextState,
                self::EXEC_ACTION => $execAction,
                self::EXEC_GUARD => $execGuard,
                self::EXEC_BEFORE => $execBefore,
                self::EXEC_AFTER => $execAfter,
            ];
        }
        return $this;
    }

    public function addCommonTransition($currentEvent, $nextState,
                                        \Closure $execAction = null,
                                        \Closure $execGuard = null,
                                        \Closure $execBefore = null,
                                        \Closure $execAfter = null)
    {
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $states = array_keys($this->sm);
        foreach ($states as $state) {
            $this->addTransition($state, $currentEvent, $nextState,
                                    $execAction, $execGuard, $execBefore, $execAfter);
        }

        return $this;
    }

    /**
     * @param $event
     * @noinspection PhpArrayPushWithOneElementInspection
     */
    public function fireEvent($event)
    {
        $this->argumentIsValidOrFail($event);

        if ($this->transitionInProgress) {
            array_push($this->eventsQueued, $event);
            return $this;
        }
        $this->transitionInProgress = true;

        $this->eventMustExistOrFail($event);

        $transition = $this->sm[$this->currentState][$event];

        $this->nextState = $transition[self::NEXT_STATE];
        $this->currentEvent = $event;

        $this->stateMustExistOrFail($this->nextState);

        $wasGuarded = false;
        if( array_key_exists(self::EXEC_GUARD, $transition) ){
            $guard = $transition[self::EXEC_GUARD];
            if ($guard) {
                if (($guard)($this) === false)
                    $wasGuarded = true;
            }
        }
        if ( ! $wasGuarded) {
            if( array_key_exists(self::EXEC_BEFORE, $transition) ) {
                $before = $transition[self::EXEC_BEFORE];
                if ($before) {
                    ($before)($this);
                }
            }
            if( array_key_exists(self::EXEC_ACTION, $transition) ) {
                $action = $transition[self::EXEC_ACTION];
                if ($action) {
                    ($action)($this);
                }
            }
            if( array_key_exists(self::EXEC_AFTER, $transition) ) {
                $after = $transition[self::EXEC_AFTER];
                if ($after) {
                    ($after)($this);
                }
            }
        }

        if( $this->cancelTransition || $wasGuarded ){
            $this->cancelTransition = false;
        } else {
            $this->currentState = $transition[self::NEXT_STATE];
        }

        $this->transitionInProgress = false;
        $event = array_shift($this->eventsQueued);
        if(  $event != null ){
            $this->fireEvent($event);
        }

        return $this;
    }

    public function can($event)
    {
        $this->argumentIsValidOrFail($event);

        try{
            $this->eventMustExistOrFail($event);
        } catch(\InvalidArgumentException $e){
            return false;
        }

        $transition = $this->sm[$this->currentState][$event];

        $this->nextState = $transition[self::NEXT_STATE];
        $this->currentEvent = $event;

        $this->stateMustExistOrFail($this->nextState);

        if( ! array_key_exists(self::EXEC_GUARD, $transition)){
            return true;
        }

        $can = true;
        $guard = $transition[self::EXEC_GUARD];
        if( $guard ){
            if( ($guard)($this) === false )
                $can = false;
        }
        return $can;
    }

    public function cancelTransition()
    {
        $this->cancelTransition = true;
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    public function getCurrentEvent()
    {
        return $this->currentEvent;
    }

    public function getNextState()
    {
        return $this->nextState;
    }

    public function getMachineToArray()
    {
        return $this->sm;
    }

    /**
     * All possible transitions from current state.
     *
     * @return array
     */
    /*public function getPossibleTransitions(){
        // TODO: pending to implementation
    }*/

    private function argumentIsValidOrFail($arg): void
    {
        $this->argumentIsNotNullOrFail($arg);
        $this->argumentIsNotBlankOrFail($arg);
    }

    private function argumentIsNotNullOrFail($arg): void
    {
        if( $arg === null )
            throw new \InvalidArgumentException("Null is not an valid argument");
    }

    private function argumentIsNotBlankOrFail($arg): void
    {
        if( trim($arg) === "" )
            throw new \InvalidArgumentException("Blank is not an valid argument");
    }

    private function eventMustExistOrFail($event)
    {
        if( !( isset($this->sm[$this->currentState][$event]) ) )
            throw new \InvalidArgumentException("Unexpected event '{$event}' on '{$this->currentState}' state");
    }

    private function stateMustExistOrFail($state)
    {
        if( ! isset($this->sm[$state]) )
            throw new \InvalidArgumentException("Event '{$this->currentEvent}' fired to unadded '{$state}' state");
    }

    private function setCurrentStateIfThisIsInitialState($state): void
    {
        if( $this->currentState == null){
            $this->currentState = $state;
        }
    }
}
