<?php

namespace n2n\queue\impl\ephemeral;

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
		$this->store->add('Test 4 Data');
		$this->assertSame(4, count($this->getQueueItemsFromStore()));
		$this->assertSame('Test 4 Data', $this->getQueueItemsFromStore()[3]->data);
	}

	// get items variable from store by reflection (because variable is private).
	function getQueueItemsFromStore() {
		$reflectionClass = new ReflectionClass(EphemeralQueueStore::class);
		$reflectionProperty = $reflectionClass->getProperty('items');
		return $reflectionProperty->getValue($this->store);
	}

	function getFirstItemFromQueue() {
		// array_reverse and array_pop to get first item of array (ignoring unset items in array).
		$reversedItems = array_reverse($this->getQueueItemsFromStore());
		return array_pop($reversedItems);
	}

	function testSequentialPollAndAck(): void {
		$this->assertSame(3, count($this->getQueueItemsFromStore()));
		$polledItemRef = $this->store->poll();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(3, $items);
		$this->assertTrue($items[0]->processing);
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);
		$this->assertSame('Test 1 Data', $polledItemRef->data);

		$polledItemRef->ack();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(2, $items);
		$this->assertFalse(isset($items[0]));
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);

		$polledItemRef = $this->store->poll();
		$this->assertSame('Test 2 Data', $polledItemRef->data);

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(2, $items);
		$this->assertFalse(isset($items[0]));
		$this->assertTrue($items[1]->processing);
		$this->assertFalse($items[2]->processing);

		$polledItemRef->ack();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(1, $items);
		$this->assertFalse(isset($items[1]));
		$this->assertFalse($items[2]->processing);
	}

	function testAsyncPoll(): void {
		$polledItemRef1 = $this->store->poll();
		$polledItemRef2 = $this->store->poll();
		$polledItemRef3 = $this->store->poll();
		$polledItemRef1->ack();
		$polledItemRef2->ack();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(1, $items);

		$polledItemRef3->ack();
		$items = $this->getQueueItemsFromStore();
		$this->assertCount(0, $items);

		$polledItemRef4 = $this->store->poll();
		$this->assertNull($polledItemRef4);
		//$polledItemRef4->ack();
	}

	function testRequeue(): void {
		$this->assertSame(3, count($this->getQueueItemsFromStore()));
		$polledItemRef = $this->store->poll();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(3, $items);
		$this->assertTrue($items[0]->processing);
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);

		$polledItemRef->reject(true);

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(3, $items);
		$this->assertFalse($items[0]->processing);
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);
	}

	function testRequeueOnRefDestruct(): void {
		$this->assertSame(3, count($this->getQueueItemsFromStore()));
		$polledItemRef = $this->store->poll();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(3, $items);
		$this->assertTrue($items[0]->processing);
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);

		unset($polledItemRef);
		gc_collect_cycles();

		$items = $this->getQueueItemsFromStore();
		$this->assertCount(2, $items);
		$this->assertFalse(isset($items[0]));
		$this->assertFalse($items[1]->processing);
		$this->assertFalse($items[2]->processing);
	}
}