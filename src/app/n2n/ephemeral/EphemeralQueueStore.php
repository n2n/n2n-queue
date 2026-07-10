<?php

namespace n2n\ephemeral;

use n2n\queue\QueueStore;
use n2n\queue\PolledItemRef;

class EphemeralQueueStore implements QueueStore {

	/**
	 * ChatGPT search for "implement Queue in PHP with add poll clear acknoledge reject"
	 * https://chatgpt.com/s/t_6a50e93f68c88191b0ef4613f4e6cee1
	 */

	private array $ready = [];
	private array $processing = [];
	private int $nextId = 1;

	/**
	 * @inheritDoc
	 */
	function add(mixed $data): void {
		$id = $this->nextId++;

		$this->ready[] = [
				'id' => $id,
				'payload' => $data,
		];
	}

	/**
	 * @inheritDoc
	 */
	function poll(): ?PolledItemRef {
		if (empty($this->ready)) {
			return null;
		}

		// get first element of ready array and remove it.
		$message = array_shift($this->ready);

		$this->processing[$message['id']] = $message;

		return $message;
	}

	/**
	 * @inheritDoc
	 */
	function addAndPoll(mixed $data): ?PolledItemRef {
		$this->add($data);
		return $this->poll();
	}

	/**
	 * @inheritDoc
	 */
	function clear(): void {
		$this->ready = [];
		$this->processing = [];
	}
}