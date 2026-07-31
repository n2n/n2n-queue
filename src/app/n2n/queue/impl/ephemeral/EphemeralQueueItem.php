<?php

namespace n2n\queue\impl\ephemeral;

use PhpParser\Node\Expr\Closure;

class EphemeralQueueItem {

	public mixed $data = null;
	public bool $processing = false;
	public bool $disposed = false;

	function __construct(mixed $data) {
		$this->data = $data;
	}

	static function dispose(EphemeralQueueItem $item) {
		$item->disposed = true;
		return $item;
	}

	static function registerDisposedCallback(Closure $closure) {
		self::dispose();
	}
}