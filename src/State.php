<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\EnumInterface;

class State implements EnumInterface {
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
		public function getValue()/*:StateEnum*/ {
			return $this->s;
		}
		
		public function __toString(): string {
			if( $this->getValue() != null )
				return $this->getValue()->__toString();
			return parent::toString();
		}
}