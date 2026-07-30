<?php

namespace n2n\queue\impl\ephemeral;

use n2n\queue\QueueStorePool;
use n2n\queue\QueueStore;

class EphemeralQueueStorePool implements QueueStorePool {

	/**
	 * @var EphemeralQueueStore[]
	 */
	private array $pool;

	function __construct() {
		$this->pool = array();
	}

	function lookupQueueStore(string $namespace, string $typeName): QueueStore {
		if(!array_key_exists($namespace, $this->pool)) {
			$this->pool[$namespace] = new EphemeralQueueStore();
		}
		return $this->pool[$namespace];
	}

	function clear(): void {
		unset($this->pool);
		$this->pool = array();
	}
}