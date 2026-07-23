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


	public function lookupQueueStore(string $namespace, string $typeName): QueueStore {
		$dirFsPath = $this->dirFsPath->ext(TypeUtils::encodeNamespace($namespace));
		if (!$dirFsPath->isDir()) {
			ExUtils::try(fn () => $dirFsPath->mkdirs($this->dirPerm));
			if ($this->dirPerm !== null) {
				// chmod after mkdirs because of possible umask restrictions.
				ExUtils::try(fn () => $dirFsPath->chmod($this->dirPerm));
			}
		}

		return new FileQueueStore($typeName, $dirFsPath, $this->filePerm);
	}

	public function clear(): void {
		foreach ($this->dirFsPath->getChildren() as $fsPath) {
			(new FileQueueStore('any', $fsPath))->clear();
		}

	}

}