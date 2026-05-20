<?php

namespace n2n\queue\impl;

use n2n\cache\impl\persistence\DboCacheStore;
use n2n\spec\dbo\Dbo;
use n2n\util\io\fs\FsPath;
use n2n\cache\impl\fs\FileCacheStore;
use n2n\cache\impl\ephemeral\EphemeralCacheStore;
use n2n\util\io\fs\FsPerm;
use n2n\queue\QueueStore;
use n2n\queue\impl\fs\FileQueueStore;

class QueueStores {

	static function file(FsPath $dirPath, FsPerm $filePerm = null): QueueStore {
		return new FileQueueStore($dirPath, $filePerm);
	}

}