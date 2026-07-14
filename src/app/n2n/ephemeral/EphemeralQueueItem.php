<?php

namespace n2n\ephemeral;

use PhpParser\Node\Expr\Closure;

class EphemeralQueueItem {
	public mixed $data = null;
	public bool $processing = false;
	public bool $disposed = false;

	static function dispose() {
		$disposed = true;
	}

	function registerDisposedCallback(Closure $closure) {
		self::dispose();
	}

}