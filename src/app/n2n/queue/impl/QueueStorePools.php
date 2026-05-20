<?php

namespace n2n\queue\impl;

use n2n\queue\impl\fs\FileQueueStorePool;
use n2n\util\io\fs\FsPath;
use n2n\util\io\fs\FsPerm;

class QueueStorePools {

	static function file(FsPath $dirPath, FsPerm|string|int|null $dirPerm = null,
			FsPerm|string|int|null $filePerm = null): FileQueueStorePool {
		return new FileQueueStorePool($dirPath, $dirPerm, $filePerm);
	}
}