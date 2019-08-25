<?php

namespace jagarsoft\StateMachine;

use  jagarsoft\StateMachine\State;
use  jagarsoft\StateMachine\Event;

// declare(strict_types=1);

class StateMachine {
	protected $stateTransitions = [];
	protected $actionTransitions = [];

	protected $sm = array();
	
	// State currentState = null;
	
	/*public function addState(State $state) {
		$state_transition = [ 'event' => null, 'state' => null ];
		$action_transition = [ 'event' => null, 'action' => null ];
		
		$this->stateTransitions[$state->getState()] = $state_transition;
		$this->actionTransitions[$state->getState()] = $action_transition;
	}*/
	
	/*public function addTransition(State $currState, Event $currEvent, State $nextState, / *Action* / $execAction) {
		
		$state_transition = &$this->stateTransitions[$currState->getState()];
	    $action_transition = &$this->actionTransitions[$currState->getState()];
		
		$state_transition['event'] = $action_transition['event'] = $currEvent;
		$state_transition['state'] = $currState;
		$action_transition['action'] = $execAction;
	}*/
    /*public function addState(State $state) {
        $this->sm[$state->getState()] = array();
    }*/

public function addTransition(/*State*/ $currState, /*Event*/ $currEvent, /*State*/ $nextState, /*Action*/ $execAction) {
    $this->sm[$currState->getState()] = [
                                $currEvent->getEvent() => [
                                                'nextState' => $nextState,
                                                'execAction' => $execAction
                                ]
                            ];
}

    /**
     * @param $evento_a
     */
    public function fireEvent($currentState, $evento_a): void
    {
        $this->sm[$currentState->getState()][$evento_a->getEvent()]['nextState'];
        $this->sm[$currentState->getState()][$evento_a->getEvent()]['execAction']();
    }
	
	public function dumpStates(){
		var_dump($this->stateTransitions);
	}
	
	public function dumpEvents(){
		var_dump($this->actionTransitions);
	}
}
