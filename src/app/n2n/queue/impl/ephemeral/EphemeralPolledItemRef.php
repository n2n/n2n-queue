<?php

namespace n2n\queue\impl\ephemeral;

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
	}

	/**
	 * @inheritDoc
	 */
	function reject(bool $requeue = false): void {
		$this->ensureProcessing();

		if ($requeue == false) {
			$this->removeCallback->__invoke();
		} else {
			$this->requeueCallback->__invoke();
		}
	}

	function __destruct() {
		//$this->item->dispose();
		/* if ($this->isActive()) {
			$this->reject(true);
		}*/
	}
}