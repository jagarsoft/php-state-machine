<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\Stubs\StateEnum;

class State {
		protected $s = null; // StateEnum
		
		/**
		 * Contructor que etiqueta el estado con una constante
		 * 
		 * @param s
		 */
		public function __construct(/*StateEnum*/ $s){
			$this->s = $s;
		}
		
		/**
		 * Obtiene la etiqueta asociada
		 * 
		 * @return La constante
		 */
		public function getState()/*:StateEnum*/ {
			return $this->s;
		}
		
		public function __toString(): string {
			if( $this->getState() != null )
				return $this->getState()->__toString();
			return parent::toString();
		}
}