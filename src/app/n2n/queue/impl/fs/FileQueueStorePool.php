<?php

namespace n2n\queue\impl\fs;

use n2n\util\io\fs\FsPath;
use n2n\util\type\TypeUtils;
use n2n\util\ex\ExUtils;
use n2n\util\io\fs\FsPerm;
use n2n\queue\QueueStorePool;
use n2n\queue\QueueStore;

class FileQueueStorePool implements QueueStorePool {

	function __construct(private FsPath $dirFsPath, private FsPerm|string|int|null $dirPerm = null,
			private FsPerm|string|int|null $filePerm = null) {

	}

	public function lookupQueueStore(string $namespace): QueueStore {
		$dirFsPath = $this->dirFsPath->ext(TypeUtils::encodeNamespace($namespace));
		if (!$dirFsPath->isDir()) {
			ExUtils::try(fn () => $dirFsPath->mkdirs($this->dirPerm));
			if ($this->dirPerm !== null) {
				// chmod after mkdirs because of possible umask restrictions.
				ExUtils::try(fn () => $dirFsPath->chmod($this->dirPerm));
			}
		}

		return new FileQueueStore($dirFsPath, $this->filePerm);
	}

	public function clear(): void {
		$this->dirFsPath->delete();
	}

}