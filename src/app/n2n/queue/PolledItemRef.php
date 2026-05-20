<?php

namespace n2n\queue;


interface PolledItemRef {

	function ack(): void;

	function reject(bool $requeue = false): void;

	public mixed $data { get; }
}