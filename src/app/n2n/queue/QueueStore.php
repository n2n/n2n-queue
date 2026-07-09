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
namespace n2n\queue;

/**
 * Defines thread safe queue used by some n2n modules.
 */
interface QueueStore {

	/**
	 * Adds an item to the queue.
	 */
	function add(mixed $data): void;

	/**
	 * Polls an item from the queue. Items which were already polled and not yet handled with
	 * {@link PolledItemRef::ack()} or {@link PolledItemRef::reject()}, will be skipped.
	 *
	 * @return PolledItemRef|null
	 */
	function poll(): ?PolledItemRef;

	/**
	 * Adds an item like {@link self::add()} and returns a PolledItemRef for this exact item
	 * as in {@link self::poll()} if this item was next in line to be polled.
	 *
	 * @param mixed $data
	 * @return PolledItemRef|null
	 */
	function addAndPoll(mixed $data): ?PolledItemRef;


	/**
	 * Empties the whole queue.
	 */
	function clear(): void;
}