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
 */namespace n2n\queue\impl;

use n2n\queue\impl\fs\FileQueueStorePool;
use n2n\util\io\fs\FsPath;
use n2n\util\io\fs\FsPerm;

class QueueStorePools {

	static function file(FsPath $dirPath, FsPerm|string|int|null $dirPerm = null,
			FsPerm|string|int|null $filePerm = null): FileQueueStorePool {
		return new FileQueueStorePool($dirPath, $dirPerm, $filePerm);
	}
}