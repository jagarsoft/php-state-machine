<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\Stubs\StateMachineBuilder;

class StateMachine {
    private const NEXT_STATE = 0;
    private const EXEC_ACTION = 1;
    
	protected $sm = array();
	protected $currentState = null;
    protected $currentEvent = null;
    protected $nextState = null;

    private $cancelTransition = false;
    private $transitionInProgress = false;
    private $eventsQueued = [];
    private $commonTransition = [];
	
	public function __construct(StateMachineBuilder $smb = null)
    {
        if(  $smb != null )
            $sm = $smb->__invoke();

        if( ! empty($sm) ){
            // StateEnum::CURRENT_STATE => [ EventEnum::ON_EVENT => [ StateEnum::NEXT_STATE_2, ActionClosureOrFunction ]  ],
            foreach ($sm as $state => $transition){
                $this->addState($state);
                foreach ($transition as $onEvent => $nextStateAndAction) {
                    if( array_key_exists(self::EXEC_ACTION, $nextStateAndAction) ) {
                        $this->addTransition($state, $onEvent, $nextStateAndAction[self::NEXT_STATE], $nextStateAndAction[self::EXEC_ACTION]);
                    } else {
                        $this->addTransition($state, $onEvent, $nextStateAndAction[self::NEXT_STATE]);
                    }
                }
            }
        }
    }

    public function addState($state)
    {
	    $this->argumentIsValidOrFail($state);

        $this->setCurrentStateIfThisIsInitialState($state);

        $this->sm[$state] = array();

        return $this;
    }

    public function addTransition($currentState, $currentEvent, $nextState, \Closure $execAction = null )
    {
        $this->argumentIsValidOrFail($currentState);
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $this->setCurrentStateIfThisIsInitialState($currentState);

        $this->sm[$currentState][$currentEvent] = [
                                                self::NEXT_STATE => $nextState,
                                                self::EXEC_ACTION => $execAction
                                            ];
        return $this;
    }

    public function addCommonTransition($currentEvent, $nextState, \Closure $execAction = null )
    {
        $this->argumentIsValidOrFail($currentEvent);
        $this->argumentIsValidOrFail($nextState);

        $this->commonTransition[$currentEvent] = [
                                                    self::NEXT_STATE => $nextState,
                                                    self::EXEC_ACTION => $execAction
                                                 ];
        return $this;
    }

    /**
     * @param $event
     */
    public function fireEvent($event)
    {
        $this->argumentIsValidOrFail($event);

        if( $this->transitionInProgress ){
            array_push($this->eventsQueued, $event);
            return $this;
        }
        $this->transitionInProgress = true;

        $this->eventMustExistOrFail($event);

        if( isset($this->commonTransition[$event]) ){
            $transition = $this->commonTransition[$event];
        } else {
            $transition = $this->sm[$this->currentState][$event];
        }
        $this->nextState = $transition[self::NEXT_STATE];

        $this->stateMustExistOrFail($this->nextState);

        $this->currentEvent = $event;

        $action = $transition[self::EXEC_ACTION];
        if( $action ){
            ($action)($this);
        }

        if( $this->cancelTransition ){
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

    public function cancelTransition(){
        $this->cancelTransition = true;
    }

    public function getCurrentState(){
        return $this->currentState;
    }

    public function getCurrentEvent(){
        return $this->currentEvent;
    }

    public function getNextState(){
        return $this->nextState;
    }

    public function getMachineToArray(){
        return $this->sm;
    }

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
        if( !( isset($this->sm[$this->currentState][$event]) || isset($this->commonTransition[$event]) ) )
            throw new \InvalidArgumentException("Unexpected event {$event} on {$this->currentState} state");
    }

    private function stateMustExistOrFail($state)
    {
        if( ! isset($this->sm[$state]) )
            throw new \InvalidArgumentException("Event '{$this->currentEvent}' fired an unexpected '{$state}' state");
    }

    private function setCurrentStateIfThisIsInitialState($state): void
    {
        if( $this->currentState == null){
            $this->currentState = $state;
        }
    }

	public function dumpStates(){
		var_dump(array_keys($this->sm));
	}
	
	public function dumpEvents(){
		var_dump($this->sm);
	}
}
