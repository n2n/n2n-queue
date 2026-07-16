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
		$this->items[] = new EphemeralQueueItem($data);
	}

	function poll(): ?PolledItemRef {

		//$firstItem = $this->items[0];
		$firstItem = array_shift($this->items);
		//$firstItem->ack();
		//return $firstItem;
		return new EphemeralPolledItemRef($firstItem);
	}

	function addAndPoll(mixed $data): ?PolledItemRef {

		$this->add($data);
		$poll = $this->poll();
		return $poll;
	}

	function clear(): void {
		$this->items = array();
	}
}