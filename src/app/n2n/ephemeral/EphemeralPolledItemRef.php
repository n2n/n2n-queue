<?php

namespace n2n\ephemeral;

use n2n\queue\PolledItemRef;
use n2n\util\ex\IllegalStateException;

class EphemeralPolledItemRef implements PolledItemRef {


	function __construct(private EphemeralQueueItem $item,
			private \Closure $requeueCallback, private \Closure $removeCallback) {
		IllegalStateException::assertTrue($item->processing);
	}

	public mixed $data {
		get => $this->item->data;
	}

	private function ensureProcessing(): void {
		if ($this->item->processing) {
			return;
		}

		throw new IllegalStateException('Cannot ack() or reject() item which is already acked or rejected.');
	}

	/**
	 * @inheritDoc
	 */
	function ack(): void {
		$this->ensureProcessing();

		$this->removeCallback->__invoke();

		// TODO: Implement ack() method.
		/* if(!$this->item->processing && !$this->item->disposed) {
			$this->item->dispose();
		} */
	}

	/**
	 * @inheritDoc
	 */
	function reject(bool $requeue = false): void {
		$this->ensureProcessing();

		// TODO: Implement reject() method.
		if($requeue == false) {
			$this->ack();
		} else {
			// TODO: put message back in queue
			$this->requeueCallback->__invoke();
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