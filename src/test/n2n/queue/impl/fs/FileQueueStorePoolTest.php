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
use n2n\queue\impl\QueueStorePools;

class FileQueueStorePoolTest extends TestCase {
	private FsPath $tempDirFsPath;

	function setUp(): void {
		$tempfile = tempnam(sys_get_temp_dir(),'');
		if (file_exists($tempfile)) {
			unlink($tempfile);
		}
		mkdir($tempfile);

		$this->tempDirFsPath = new FsPath($tempfile);
	}

	function testLookup() {
		$pool = QueueStorePools::file($this->tempDirFsPath, 0777, 0777);

		$pool->lookupQueueStore('ns\\ns1', 'string')->add('name', ['prop' => 'huii']);
		$pool->lookupQueueStore('ns\\ns2', 'string')->add('name', ['prop' => 'huii']);

		$this->assertCount(2, $this->tempDirFsPath->getChildren());
		$this->assertTrue($this->tempDirFsPath->ext('ns-ns1')->exists());
		$this->assertTrue($this->tempDirFsPath->ext('ns-ns2')->exists());
	}


	function testClear() {
		$pool = QueueStorePools::file($this->tempDirFsPath, 0777, 0777);

		$pool->lookupQueueStore('ns\\ns1', 'string')->add('huii');
		$pool->lookupQueueStore('ns\\ns2', 'string')->add('huii');

		$this->assertCount(2, $this->tempDirFsPath->getChildren());

		$pool->clear();

		$this->assertCount(0, $this->tempDirFsPath->getChildren());
	}

}