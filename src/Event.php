<?php

namespace jagarsoft\StateMachine;

use jagarsoft\StateMachine\Stubs\EventEnum;

class Event {
		protected $e = null; // EventEnum
		
		public function __construct(/*EventEnum*/ $e){
			$this->e = $e;
		}
		
		/**
		 * Obtiene la etiqueta asociada
		 * 
		 * @return La constante
		 */
		public function getEvent()/*:EventEnum*/ {
			return $this->e;
		}
		
		public function __toString(): string {
			if( $this->getEvent() != null )
				return $this->getEvent()->toString();
			return parent::toString();
		}
}