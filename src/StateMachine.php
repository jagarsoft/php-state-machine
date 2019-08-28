<?php

namespace jagarsoft\StateMachine;

class StateMachine {
    private const NEXT_STATE = 0;
    private const EXEC_ACTION = 1;
    
	protected $sm = array();
	protected $currentState = null;
    protected $currentEvent = null;
	
	public function __construct(Array $sm = [])
    {
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

    public function addTransition($currState, $currEvent, $nextState, \Closure $execAction = null )
    {
        $this->argumentIsValidOrFail($currState);
        $this->argumentIsValidOrFail($currEvent);
        $this->argumentIsValidOrFail($nextState);

	    $this->setCurrentStateIfThisIsInitialState($currState);

        $this->sm[$currState][$currEvent] = [
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
        $this->eventMustExistOrFail($event);

        $this->nextState = $this->sm[$this->currentState][$event][self::NEXT_STATE];

        $this->stateMustExistOrFail($this->nextState);

        $this->currentEvent = $event;

        $action = $this->sm[$this->currentState][$event][self::EXEC_ACTION];
        if( $action ){
            ($action)($this);
        }
        $this->currentState = $this->sm[$this->currentState][$event][self::NEXT_STATE];

        return $this;
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
        if( ! isset($this->sm[$this->currentState][$event]) )
            throw new \InvalidArgumentException("Unexpected event {$event} on {$this->currentState} state");
    }

    private function stateMustExistOrFail($state)
    {
        if( ! isset($this->sm[$this->currentState]) )
            throw new \InvalidArgumentException("Event {$this->currentEvent} fired an unexpected {$this->currentState} state");
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
