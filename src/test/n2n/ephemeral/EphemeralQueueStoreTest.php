<?php

namespace n2n\ephemeral;

use PHPUnit\Framework\TestCase;

class EphemeralQueueStoreTest extends TestCase {

	private $store;

	function setUp():void {
		$store = new EphemeralQueueStore();
		$store->add('Test 1 Data');
		$store->add('Test 2 Data');
		$store->add('Test 3 Data');
		$this->store = $store;
	}

	function testAddNewItemToQueue(): void {
		$this->assertSame(3, count($this->store->items));
	}

	function testPollFromQueue(): void {
		$polledItemRef = $this->store->poll();
		$this->assertSame(2, count($this->store->items));
	}

	function testClearAllItemsInQueue(): void {
		$this->store->clear();
		$this->assertSame(0, count($this->store->items));
	}

	function testAddAndPollFromQueue(): void {
		$this->store->addAndPoll('Test 4 Data');
		$this->assertSame(3, count($this->store->items));
	}
}