<?php

namespace n2n\ephemeral;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EphemeralQueueStoreTest extends TestCase {

	private EphemeralQueueStore $store;

	function setUp():void {
		$store = new EphemeralQueueStore();
		$store->add('Test 1 Data');
		$store->add('Test 2 Data');
		$store->add('Test 3 Data');
		$this->store = $store;
	}

	function testAddNewItemToQueue(): void {
		$this->assertSame(3, count($this->getQueueItemsFromStore()));
	}

	function testPollFromQueue(): void {
		$polledItemRef = $this->store->poll();
		$this->assertSame(2, count($this->getQueueItemsFromStore()));

		$polledItemRef = $this->store->poll();
		$this->assertSame(1, count($this->getQueueItemsFromStore()));

		$firstItemData = $this->getFirstItemFromQueue()->data;
		$this->assertSame('Test 3 Data', $firstItemData);
	}

	function testAddAndPollFromQueue(): void {
		$this->store->addAndPoll('Test 4 Data');
		$this->assertSame(3, count($this->getQueueItemsFromStore()));

		$this->assertSame('Test 2 Data', $this->getFirstItemFromQueue()->data);
	}

	function testClearAllItemsInQueue(): void {
		// initial number of items in array.
		$this->assertSame(3, count($this->getQueueItemsFromStore()));

		// no more items exist in array.
		$this->store->clear();
		$this->assertSame(0, count($this->getQueueItemsFromStore()));
	}

	// Returns the element at the front without removing it.
	function testGetPeek(): void {
		$this->assertSame('Test 1 Data', $this->getFirstItemFromQueue()->data);

		$polledItemRef = $this->store->poll();
		$this->assertSame('Test 2 Data', $this->getFirstItemFromQueue()->data);
	}

	// get items variable from store by reflection (because variable is private).
	function getQueueItemsFromStore() {
		$reflectionClass = new ReflectionClass('n2n\ephemeral\EphemeralQueueStore');
		$reflectionProperty = $reflectionClass->getProperty('items');
		return $reflectionProperty->getValue($this->store);
	}

	function getFirstItemFromQueue() {
		// array_reverse and array_pop to get first item of array (ignoring unset items in array).
		$reversedItems = array_reverse($this->getQueueItemsFromStore());
		$firstItem = array_pop($reversedItems);
		return $firstItem;
	}
}