<?php

namespace n2n\ephemeral;

use n2n\queue\PolledItemRef;

class EphemeralPolledItemRef implements PolledItemRef {

	private EphemeralQueueItem $item;

	function __construct(public mixed $data) {
	}

	/**
	 * @inheritDoc
	 */
	function ack(): void {
		// TODO: Implement ack() method.
		/* if(!$this->item->processing && !$this->item->disposed) {
			$this->item->dispose();
		} */
	}

	/**
	 * @inheritDoc
	 */
	function reject(bool $requeue = false): void {
		// TODO: Implement reject() method.
		if($requeue == false) {
			$this->ack();
		} else {
			// TODO: put message back in queue
			return;
		}
	}

	function __destruct() {
		//$this->item->dispose();
		/* if ($this->isActive()) {
			$this->reject(true);
		}*/
	}
}