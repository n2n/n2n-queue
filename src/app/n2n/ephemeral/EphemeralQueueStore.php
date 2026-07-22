<?php

namespace n2n\ephemeral;

use n2n\queue\QueueStore;
use n2n\queue\PolledItemRef;
use n2n\util\col\ArrayUtils;

class EphemeralQueueStore implements QueueStore {

	/**
	 * @var EphemeralQueueItem[]
	 */
	private array $items;

	function __construct() {
		$this->items = array();
	}

	function add(mixed $data): void {
		$this->items[] = new EphemeralQueueItem($data);
	}

	function poll(): ?PolledItemRef {
		foreach ($this->items as $item) {
			if ($item->processing) {
				continue;
			}

			$item->processing = true;
			$itemRef = new EphemeralPolledItemRef($item,
					fn () => $item->processing = false,
					fn () => ArrayUtils::unsetByValue($this->items, $item));
			return $itemRef;
		}

		return null;

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