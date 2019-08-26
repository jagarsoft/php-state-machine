<?php

namespace jagarsoft\StateMachine;

use  jagarsoft\StateMachine\State;
use  jagarsoft\StateMachine\Event;

// declare(strict_types=1);

class StateMachine {
	protected $sm = array();
	
	protected /*State*/ $currentState = null;
	
	public function __construct(Array $sm = [])
    {
        if( ! empty($sm) ){
            // StateEnum::CURRENT_STATE => [ EventEnum::ON_EVENT => [ StateEnum::NEXT_STATE_2, ActionClosureOrFunction ]  ],
            foreach ($sm as $state => $transition){
                $this->addState(new State($state));
                foreach ($transition as $onEvent => $nextStateAndAction) {
                    if( array_key_exists(1, $nextStateAndAction) ) {
                        $this->addTransition(new State($state), new Event($onEvent), new State($nextStateAndAction[0]), $nextStateAndAction[1]);
                    } else {
                        $this->addTransition(new State($state), new Event($onEvent), new State($nextStateAndAction[0]));
                    }
                }
            }
        }
    }

    public function addState(State $state) {
        $this->setCurrentStateIfThisIsInitialState($state);

        $this->sm[$state->getValue()] = array();
    }

public function addTransition(State $currState, Event $currEvent, State $nextState, /*Action*/ \Closure $execAction = null ) {
    $this->setCurrentStateIfThisIsInitialState($currState);

    /*$eventsList = $this->sm[$currState->getValue()];
    $eventsList[$currEvent->getValue()] = [
                                          'nextState' => $nextState,
                                          'execAction' => $execAction
                                        ];*/
    $this->sm[$currState->getValue()][ $currEvent->getValue()] = [
                                            'nextState' => $nextState,
                                            'execAction' => $execAction
                                            ];
}

    /**
     * @param $evento_a
     */
    public function fireEvent(Event $evento_a): void
    {
        $action = $this->sm[$this->currentState->getValue()][$evento_a->getValue()]['execAction'];
        if( $action ){
            ($action)();
        }
        $this->currentState = $this->sm[$this->currentState->getValue()][$evento_a->getValue()]['nextState'];
    }

    private function setCurrentStateIfThisIsInitialState(State $state){
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

	public function getMachineToArray(){
	    return $this->sm;
    }
}
