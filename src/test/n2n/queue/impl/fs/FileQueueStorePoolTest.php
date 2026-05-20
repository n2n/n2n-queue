<?php

namespace n2n\queue\impl\fs;

use PHPUnit\Framework\TestCase;
use n2n\util\io\fs\FsPath;
use n2n\cache\CharacteristicsList;
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

		$pool->lookupQueueStore('ns\\ns1')->add('name', ['prop' => 'huii']);
		$pool->lookupQueueStore('ns\\ns2')->add('name', ['prop' => 'huii']);

		$this->assertCount(2, $this->tempDirFsPath->getChildren());
		$this->assertTrue($this->tempDirFsPath->ext('ns-ns1')->exists());
		$this->assertTrue($this->tempDirFsPath->ext('ns-ns2')->exists());
	}


	function testClear() {
		$pool = QueueStorePools::file($this->tempDirFsPath, 0777, 0777);

		$pool->lookupQueueStore('ns\\ns1')->add(['prop' => 'huii']);
		$pool->lookupQueueStore('ns\\ns2')->add(['prop' => 'huii']);

		$this->assertCount(2, $this->tempDirFsPath->getChildren());

		$pool->clear();

		$this->assertCount(0, $this->tempDirFsPath->getChildren());
	}

}