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

use n2n\util\io\IoUtils;
use n2n\util\io\IoException;
use n2n\queue\ex\QueueOperationFailedException;
use n2n\queue\QueueStore;
use n2n\util\io\fs\FsPath;
use n2n\queue\PolledItemRef;
use n2n\concurrency\sync\impl\Sync;
use n2n\util\StringUtils;
use n2n\util\io\fs\FsPerm;
use n2n\util\ex\ExUtils;
use n2n\concurrency\sync\impl\fs\FileLock;
use n2n\util\serialize\SerializationUtils;
use n2n\util\serialize\ex\TypeNotSupportedForSerializationException;
use n2n\util\ex\IllegalStateException;
use n2n\util\ex\err\ConfigurationError;
use n2n\util\serialize\ex\UnserializationFailedException;
use n2n\util\type\TypeUtils;

/**
 * File based queue. Data will be serialized by {@link SerializationUtils::strictObjSerialize()} and written to a file.
 *
 * @template T
 * @extends QueueStore<T>
 */
class FileQueueStore implements QueueStore {

	const LOCK_FOLDER = 'lock';
	const DATA_FOLDER = 'data';
    const DATA_FILE_SUFFIX = '.dat';
	const LOCK_FILE_SUFFIX = '.lock';


	private FsPath $dataDirFsPath;
	private FsPath $lockDirFsPath;

	/**
	 * @param class-string<T> $typeName
	 * @param FsPath $dirFsPath
	 * @param FsPerm|string|int|null $filePerm
	 * @param string|null $dataClassName used for {@link SerializationUtils::strictObjSerialize()} and
	 * 		{@link SerializationUtils::strictObjUnserialize()}
	 * @param int|null $maxItemSize maximum serialized size of a single item in bytes; null disables the cap.
	 * 		On {@see self::add()} an item exceeding it is rejected with a {@link QueueOperationFailedException}.
	 * 		On {@see self::poll()} an oversized file is treated as corrupt (deleted) so it cannot exhaust
	 * 		memory through {@see IoUtils::getContents()}. See {@link SerializationUtils} — input size must be
	 * 		capped at the call site when data is untrusted.
	 */
    function __construct(private string $typeName, FsPath $dirFsPath, private FsPerm|string|int|null $filePerm = null,
			?string $dataClassName = null, private ?int $maxItemSize = null) {
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
		// try finally theoretically not necessary because __destruct of FileLock would relaes lock on failure anyway.
		try {
			$this->putContents($fsPath, $data);
		} finally {
			$fileLock->release();
		}
    }

	private function putContents(FsPath $fileFsPath, mixed $data): mixed {
		try {
			$ser = SerializationUtils::checkedStrictSerialize($data, $this->typeName);
			if ($this->maxItemSize !== null && strlen($ser) > $this->maxItemSize) {
				throw new QueueOperationFailedException(sprintf(
						'Item exceeds maxItemSize of %d bytes (serialized size %d bytes).',
						$this->maxItemSize, strlen($ser)));
			}
			// round-trip to validate the item is (un)serializable for this type before committing it.
			$data = SerializationUtils::checkedStrictUnserialize($ser, $this->typeName);
			IoUtils::putContents($fileFsPath, $ser);
		} catch (IoException|UnserializationFailedException $e) {
			throw new QueueOperationFailedException(previous: $e);
		} catch (TypeNotSupportedForSerializationException $e) {
			throw new ConfigurationError(static::class . ' does not support type ' . $this->typeName
					. ' Reason: ' . $e->getMessage(), previous: $e);
		}

		if ($this->filePerm !== null) {
			ExUtils::try(fn () => $fileFsPath->chmod($this->filePerm));
		}

		return $data;
	}

	/**
	 * @throws UnserializationFailedException
	 */
	private function readContents(FsPath $fileFsPath): mixed {
		try {
			if ($this->maxItemSize !== null && $fileFsPath->getSize() > $this->maxItemSize) {
				// Treat as corrupt so poll() deletes the oversized file rather than slurping it into
				// memory via getContents().
				throw new UnserializationFailedException(sprintf(
						'Item exceeds maxItemSize of %d bytes (file size %d bytes).',
						$this->maxItemSize, $fileFsPath->getSize()));
			}
			return SerializationUtils::checkedStrictUnserialize(IoUtils::getContents($fileFsPath), $this->typeName);
		} catch (IoException $e) {
			throw new QueueOperationFailedException(previous: $e);
		} catch (TypeNotSupportedForSerializationException $e) {
			throw new ConfigurationError(static::class . ' does not support type ' . $this->typeName
					. ' Reason: ' . $e->getMessage(), previous: $e);
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

			try {
				return $this->createPolledItemRef($fsPath, $fileLock);
			} catch (UnserializationFailedException $e) {
				trigger_error(static::class . ': Corrupted file "' . $fsPath
						. '" will be deleted due to unserialization error: ' . $e->getMessage());
				$fsPath->delete();
			}
		}

		return null;
	}

	function addAndPoll(mixed $data): ?PolledItemRef {
		$fsPath = $this->createNewDataFsPath();
		$fileLock = Sync::byFileLock($this->createLockFsPath($fsPath));
		ExUtils::try(fn () => $fileLock->acquire());
		$data = $this->putContents($fsPath, $data);


		return $this->createPolledItemRef($fsPath, $fileLock);

	}

	/**
	 * @throws UnserializationFailedException
	 */
	private function createPolledItemRef(FsPath $fsPath, FileLock $fileLock): PolledItemRef {
		$data = $this->readContents($fsPath);
		return new FilePolledItemRef($fsPath, $fileLock, $data);
	}

	function clear(): void {
		foreach ($this->dataDirFsPath->getChildren() as $fsPath) {
			$fsPath->delete();
		}
	}
}