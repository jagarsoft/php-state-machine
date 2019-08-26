<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\EnumInterface;

class Event implements EnumInterface {
		protected $e = null; // EventEnum
		
		public function __construct(/*EventEnum*/ $e){
			$this->e = $e;
		}
		
		/**
		 * Obtiene la etiqueta asociada
		 * 
		 * @return La constante
		 */
		public function getValue()/*:EventEnum*/ {
			return $this->e;
		}
		
		public function __toString(): string {
			if( $this->getValue() != null )
				return $this->getValue()->toString();
			return parent::toString();
		}
}