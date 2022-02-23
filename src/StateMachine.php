<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\StateMachineBuilder;
use phpDocumentor\Reflection\Types\Array_;

class StateMachine
{
    public const NEXT_STATE = 0;
    public const EXEC_ACTION = 'EXEC_ACTION';
    public const EXEC_GUARD = 'EXEC_GUARD';
    public const EXEC_BEFORE = 'EXEC_BEFORE';
    public const EXEC_AFTER = 'EXEC_AFTER';

    protected $sm = [];
    protected $currentState = null;
    protected $currentEvent = null;
    protected $nextState = null;

    protected $cancelTransition = false;
    protected $transitionInProgress = false;
    protected $eventsQueued = [];

    public function __construct(StateMachineBuilder $smb = null)
    {
        if ($smb != null)
            $this->smb = $smb->from();

        if (!empty($this->smb)) {
            // StateEnum::CURRENT_STATE => [ EventEnum::ON_EVENT => [ NEXT_STATE, ClosureOrFunctionsArray ] ],
            foreach ($this->smb as $state => $transition) {
                $this->addState($state);
                foreach ($transition as $onEvent => $nextStateAndAction) {
                    $this->addTransition($state, $onEvent,
                        $nextStateAndAction[self::NEXT_STATE],
                        $nextStateAndAction);
                }
            }
        }
    }

    public function to()
    {
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

    public function addTransition($currentState, $currentEvent, $nextState, /*\Closure|array*/ $execAction = null)
    {
        $this->argumentIsValidOrFail($currentState);
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $this->setCurrentStateIfThisIsInitialState($currentState);

        if( $execAction === null ){
            $this->sm[$currentState][$currentEvent] = [ self::NEXT_STATE => $nextState ];
        } elseif (is_array($execAction)) {
            $arrayActions = $execAction;
            $this->sm[$currentState][$currentEvent] = [ self::NEXT_STATE => $nextState ] + $arrayActions;
        } elseif($execAction instanceof \Closure) {
            $this->sm[$currentState][$currentEvent] = [
                self::NEXT_STATE => $nextState,
                self::EXEC_ACTION => $execAction
            ];
        } else {
            throw new \InvalidArgumentException('execAction argument must be Closure or array');
        }
        return $this;
    }

    public function addCommonTransition($currentEvent, $nextState, /*\Closure|array*/ $execAction = null)
    {
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $states = array_keys($this->sm);
        foreach ($states as $state) {
            $this->addTransition($state, $currentEvent, $nextState, $execAction);
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

        $wasGuarded = $this->execGuard($transition);

        if (!$wasGuarded) {
            $this->execAction(self::EXEC_BEFORE, $transition);
            $this->execAction(self::EXEC_ACTION, $transition);
            $this->execAction(self::EXEC_AFTER, $transition);
        }

        if ($this->cancelTransition || $wasGuarded) {
            $this->cancelTransition = false;
        } else {
            $this->currentState = $this->nextState;
        }

        $this->transitionInProgress = false;
        $event = array_shift($this->eventsQueued);
        if ($event != null) {
            $this->fireEvent($event);
        }

        return $this;
    }

    private function execGuard($transition)
    {
        $wasGuarded = false;
        if( array_key_exists(self::EXEC_GUARD, $transition) ){
            $guard = $transition[self::EXEC_GUARD];
            if ($guard) {
                if (($guard)($this) === false)
                    $wasGuarded = true;
            }
        }
        return $wasGuarded;
    }

    private function execAction($actionIndex, $transition): void
    {
        if (array_key_exists($actionIndex, $transition)) {
            $action = $transition[$actionIndex];
            if ($action) {
                ($action)($this);
            }
        }
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

        $nextState = $this->nextState;
        $currentEvent = $this->currentEvent;
        $this->nextState = $transition[self::NEXT_STATE];
        $this->currentEvent = $event;

        $this->stateMustExistOrFail($this->nextState);

        $wasGuarded = ! $this->execGuard($transition);

        $this->nextState = $nextState;
        $this->currentEvent = $currentEvent;

        return $wasGuarded;
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
