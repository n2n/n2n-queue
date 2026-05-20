<?php

namespace n2n\queue;

interface QueueStorePool {

	function lookupQueueStore(string $namespace): QueueStore;

	function clear(): void;
}