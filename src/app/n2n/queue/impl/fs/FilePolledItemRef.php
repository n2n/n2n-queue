<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 */namespace n2n\queue\impl\fs;

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