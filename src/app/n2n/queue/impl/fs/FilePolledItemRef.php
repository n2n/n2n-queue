<?php

namespace n2n\queue\impl\fs;

use n2n\concurrency\sync\impl\fs\FileLock;
use n2n\queue\PolledItemRef;
use n2n\util\io\fs\FsPath;
use n2n\util\ex\IllegalStateException;

class FilePolledItemRef implements PolledItemRef {

	function __construct(private FsPath $fsPath, private FileLock $fileLock,
			public mixed $data) {
	}

	private function isActive(): bool {
		return $this->fileLock->isActive();
	}

	private function ensureActive(): void {
		IllegalStateException::assertTrue($this->isActive(),
				'FilePolledItemRef is no longer active. It was possible already acked or rejected.');
	}

	function ack(): void {
		$this->ensureActive();

		$this->fsPath->delete();
		$this->fileLock->release();
	}

	function reject(bool $requeue = false): void {
		$this->ensureActive();

		if (!$requeue) {
			$this->fsPath->delete();
		}
		$this->fileLock->release();
	}

	function __destruct() {
		if ($this->isActive()) {
			$this->reject(true);
		}
	}
}