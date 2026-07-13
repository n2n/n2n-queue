<?php

namespace n2n\ephemeral;

use n2n\queue\PolledItemRef;

class EphemeralPolledItemRef implements PolledItemRef {

	function __construct(public mixed $data) {
	}

	/**
	 * @inheritDoc
	 */
	function ack(): void {
		// TODO: Implement ack() method.
	}

	/**
	 * @inheritDoc
	 */
	function reject(bool $requeue = false): void {
		// TODO: Implement reject() method.
	}

	function __destruct() {
		/* if ($this->isActive()) {
			$this->reject(true);
		}*/
	}
}