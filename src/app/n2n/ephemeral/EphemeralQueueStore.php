<?php

namespace n2n\ephemeral;

use n2n\queue\QueueStore;
use n2n\queue\PolledItemRef;

class EphemeralQueueStore implements QueueStore {

	public $items;

	function __construct() {
		$this->items = array();
	}

	function add(mixed $data): void {
		// TODO: Implement add() method.
		$this->items[] = new EphemeralQueueItem($data);
	}

	function poll(): ?PolledItemRef {
		// TODO: Implement poll() method.

		$firstItem = $this->items[0];
		return $firstItem->ack();
		//return array_shift($this->items);
	}

	function addAndPoll(mixed $data): ?PolledItemRef {
		// TODO: Implement addAndPoll() method.

		$this->add($data);
		$poll = $this->poll();
		return $poll;
	}

	function clear(): void {
		// TODO: Implement clear() method.
		$this->items = array();
	}
}