<?php
namespace n2n\queue;

interface QueueStore {

	function add(mixed $data): void;

	function poll(): ?PolledItemRef;

	function clear(): void;
}