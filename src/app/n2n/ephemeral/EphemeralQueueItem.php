<?php

namespace n2n\ephemeral;

use PhpParser\Node\Expr\Closure;

class EphemeralQueueItem {

	public mixed $data = null;
	public bool $processing = false;
	public bool $disposed = false;

	function __construct(mixed $data) {
		$this->data = $data;
	}

	static function dispose() {
		$disposed = true;
	}

	/*
	 * https://www.geeksforgeeks.org/system-design/observer-pattern-set-1-introduction/
	 * https://refactoring.guru/design-patterns/observer
	 *
	 * https://chatgpt.com/s/t_6a58dfd42e388191b1547f207ddd7436
	 */
	static function registerDisposedCallback(Closure $closure) {
		self::dispose();
	}

}