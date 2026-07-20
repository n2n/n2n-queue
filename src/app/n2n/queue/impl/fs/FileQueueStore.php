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
 */
namespace n2n\queue\impl\fs;

use n2n\util\io\IoUtils;
use n2n\util\io\IoException;
use n2n\queue\ex\QueueOperationFailedException;
use n2n\queue\QueueStore;
use n2n\util\io\fs\FsPath;
use n2n\queue\PolledItemRef;
use n2n\concurrency\sync\impl\Sync;
use n2n\concurrency\sync\LockMode;
use n2n\util\StringUtils;
use n2n\util\io\fs\FsPerm;
use n2n\util\ex\ExUtils;
use n2n\concurrency\sync\impl\fs\FileLock;

class FileQueueStore implements QueueStore {

	const LOCK_FOLDER = 'lock';
	const DATA_FOLDER = 'data';
    const DATA_FILE_SUFFIX = '.dat';
	const LOCK_FILE_SUFFIX = '.lock';

	private FsPath $dataDirFsPath;
	private FsPath $lockDirFsPath;

    function __construct(FsPath $dirFsPath, private FsPerm|string|int|null $filePerm = null) {
		$this->dataDirFsPath = $dirFsPath->ext(self::DATA_FOLDER);
		$this->lockDirFsPath = $dirFsPath->ext(self::LOCK_FOLDER);
    }

	private function createNewDataFsPath(): FsPath {
		if (!$this->dataDirFsPath->isDir()) {
			ExUtils::try(fn () => $this->dataDirFsPath->mkdirs());
		}

		return $this->dataDirFsPath->ext(uniqid(more_entropy: true) . self::DATA_FILE_SUFFIX);
	}

	private function createLockFsPath(FsPath $dataFileFsPath): FsPath {
		if (!$this->lockDirFsPath->isDir()) {
			ExUtils::try(fn () => $this->lockDirFsPath->mkdirs());
		}

		return $this->lockDirFsPath->ext($dataFileFsPath->getFileName() . self::LOCK_FILE_SUFFIX);
	}


    function add(mixed $data): void {
		$fsPath = $this->createNewDataFsPath();
		$fileLock = Sync::byFileLock($this->createLockFsPath($fsPath));
		ExUtils::try(fn () => $fileLock->acquire());
		$this->putContents($fsPath, $data);
		$fileLock->release();
    }

	private function putContents(FsPath $fileFsPath, mixed $data): void {
		try {
			IoUtils::putContents($fileFsPath, serialize($data));
		} catch (IoException $e) {
			throw new QueueOperationFailedException(previous: $e);
		}

		if ($this->filePerm !== null) {
			ExUtils::try(fn () => $fileFsPath->chmod($this->filePerm));
		}

	}

	function poll(): ?PolledItemRef {
		$fsPaths = $this->dataDirFsPath->getChildren();
		if (empty($fsPaths)) {
			return null;
		}

		foreach ($fsPaths as $fsPath) {
			$fileLock = Sync::byFileLock($this->createLockFsPath($fsPath));
			if (!$fileLock->acquireNb()) {
				continue;
			}

			return $this->createPolledItemRef($fsPath, $fileLock);
		}

		return null;
	}

	function addAndPoll(mixed $data): ?PolledItemRef {
		$fsPath = $this->createNewDataFsPath();
		$fileLock = Sync::byFileLock($this->createLockFsPath($fsPath));
		ExUtils::try(fn () => $fileLock->acquire());
		$this->putContents($fsPath, $data);
		return $this->createPolledItemRef($fsPath, $fileLock);
	}

	private function createPolledItemRef(FsPath $fsPath, FileLock $fileLock): PolledItemRef {
		try {
			$data = StringUtils::unserialize(IoUtils::getContents($fsPath));
		} catch (IoException $e) {
			throw new QueueOperationFailedException(previous: $e);
		}

		return new FilePolledItemRef($fsPath, $fileLock, $data);
	}

	function clear(): void {
		foreach ($this->dataDirFsPath->getChildren() as $fsPath) {
			$fsPath->delete();
		}
	}
}