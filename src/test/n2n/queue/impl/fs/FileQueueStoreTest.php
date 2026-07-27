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

}