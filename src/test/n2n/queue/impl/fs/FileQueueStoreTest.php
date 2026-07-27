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

use PHPUnit\Framework\TestCase;
use n2n\util\io\fs\FsPath;
use n2n\cache\CharacteristicsList;
use n2n\concurrency\sync\impl\fs\FileLock;
use n2n\util\HashUtils;
use n2n\queue\ex\QueueOperationFailedException;

class FileQueueStoreTest extends TestCase {
	private FsPath $tempDirFsPath;

	function setUp(): void {
		$tempfile = tempnam(sys_get_temp_dir(),'');
		if (file_exists($tempfile)) { unlink($tempfile); }
		mkdir($tempfile);

		$this->tempDirFsPath = new FsPath($tempfile);
	}

	function testAck() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);
		
		$queue->add('dato');
		$polledRef = $queue->poll();

		$this->assertSame('dato', $polledRef->data);
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
		$polledRef->ack();
		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$this->assertNull($queue->poll());
	}

	function testReject() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);

		$queue->add('dato');

		$polledRef = $queue->poll();
		$this->assertSame('dato', $polledRef->data);
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
		$polledRef->reject(true);

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$polledRef = $queue->poll();
		$this->assertSame('dato', $polledRef->data);
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
		$polledRef->reject();

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$this->assertNull($queue->poll());
	}

	function testPollWithoutInit() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);

		$this->assertNull($queue->poll());
	}

	function testPollOrderWithAcquireNb() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);
		$queue->add('dato1');
		$queue->add('dato2');

		$ref1 = $queue->poll();
		$this->assertSame('dato1', $ref1->data);

		$ref2 = $queue->poll();
		$this->assertSame('dato2', $ref2->data);
	}

	function testAddAndPoll() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);
		$ref1 = $queue->addAndPoll('dato1');
		$ref2 = $queue->addAndPoll('dato2');

		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$this->assertNull($queue->poll());

		$ref1->reject(true);

		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$polledRef = $queue->poll();
		$this->assertSame('dato1', $polledRef->data);

		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$polledRef->ack();

		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$ref2->reject();

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$this->assertNull($queue->poll());
	}

	/**
	 * @throws \ReflectionException
	 */
	function testClearDoNotRemoveLockFiles() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);
		$queue->add('dato1');
		$queue->add('dato2');

		$ref1 = $queue->poll();
		$ref2 = $queue->poll();

		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$queue->clear();

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(2, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		$ref1->ack();
		$ref2->reject();

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
	}

	function testRefDestruct() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777);
		$queue->add('dato1');

		$ref1 = $queue->poll();

		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());

		unset($ref1);

		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
	}

	function testMaxItemSizeRejectsOversizedAdd() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777, null, 10);

		$queue->add('short'); // '"short"' = 7 bytes, within the 10-byte cap
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());

		try {
			$queue->add(str_repeat('x', 100)); // '"xxx..."' = 102 bytes, exceeds the cap
			$this->fail('Expected QueueOperationFailedException for oversized item');
		} catch (QueueOperationFailedException $e) {
			$this->assertStringContainsString('maxItemSize', $e->getMessage());
		}

		// the rejected add must not leave a data file behind, and its lock must have been released
		$this->assertCount(1, $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER)->getChildren());
		$this->assertCount(0, $this->tempDirFsPath->ext(FileQueueStore::LOCK_FOLDER)->getChildren());
	}

	function testMaxItemSizeDeletesOversizedOnPoll() {
		$queue = new FileQueueStore('string', $this->tempDirFsPath, 0777, null, 10);
		$dataDir = $this->tempDirFsPath->ext(FileQueueStore::DATA_FOLDER);
		$dataDir->mkdirs();

		// plant an oversized file directly in the data dir (simulates a planted/runaway item)
		$oversized = $dataDir->ext('oversized.dat');
		file_put_contents((string) $oversized, str_repeat('x', 100));
		$this->assertCount(1, $dataDir->getChildren());

		// poll() must treat the oversized file as corrupt (delete it) rather than slurping it into memory
		$this->assertNull(@$queue->poll());
		$this->assertCount(0, $dataDir->getChildren());
		$this->assertFalse(file_exists((string) $oversized));
	}

}