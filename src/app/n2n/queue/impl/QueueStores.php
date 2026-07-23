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

use n2n\cache\impl\persistence\DboCacheStore;
use n2n\spec\dbo\Dbo;
use n2n\util\io\fs\FsPath;
use n2n\cache\impl\fs\FileCacheStore;
use n2n\cache\impl\ephemeral\EphemeralCacheStore;
use n2n\util\io\fs\FsPerm;
use n2n\queue\QueueStore;
use n2n\queue\impl\fs\FileQueueStore;

class QueueStores {

	/**
	 * @see FileQueueStore for more information.
	 *
	 * @template T
	 * @param class-string<T> $typeName;
	 * @param FsPath $dirPath
	 * @param FsPerm|null $filePerm
	 * @param string|null $dataClassName used for {@link SerializationUtils::strictObjSerialize()} and
	 *        {@link SerializationUtils::strictObjUnserialize()}
	 * @return QueueStore<T>
	 */
	static function file(string $typeName, FsPath $dirPath, FsPerm $filePerm = null, ?string $dataClassName = null): QueueStore {
		return new FileQueueStore($typeName, $dirPath, $filePerm);
	}

}