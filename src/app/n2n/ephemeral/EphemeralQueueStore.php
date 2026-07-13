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
	function __construct() {

	}
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

		return new EphemeralPolledItemRef($message['id']);

		// return $message;
	}

	/**
	 * @inheritDoc
	 */
	function addAndPoll(mixed $data): ?PolledItemRef {
		$this->add($data);
		$this->poll();
		return new EphemeralPolledItemRef($data);
	}

	/**
	 * @inheritDoc
	 */
	function clear(): void {
		$this->ready = [];
		$this->processing = [];
	}





	public function acknowledge(int $id): bool {
		if (!isset($this->processing[$id])) {
			return false;
		}

		unset($this->processing[$id]);

		return true;
	}

	public function reject(int $id): bool {
		if (!isset($this->processing[$id])) {
			return false;
		}

		$message = $this->processing[$id];
		unset($this->processing[$id]);

		// Put back at the end of the queue
		$this->ready[] = $message;

		return true;
	}


	public function size(): int {
		return count($this->ready);
	}

	public function processingCount(): int {
		return count($this->processing);
	}
}