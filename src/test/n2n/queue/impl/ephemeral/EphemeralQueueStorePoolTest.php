<?php

namespace n2n\queue\impl\ephemeral;

use function PHPUnit\Framework\assertCount;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;
use ReflectionClass;

class EphemeralQueueStorePoolTest extends TestCase  {
	private EphemeralQueueStorePool $pool;

	private string $defaultNamespace;

	function setUp():void {
		$this->defaultNamespace = 'Default Pool';
		$this->pool = new EphemeralQueueStorePool();

		$store = $this->pool->lookupQueueStore($this->defaultNamespace, 'mixed');
		$store->add('Test 1 Data');
		$store->add('Test 2 Data');
		$store->add('Test 3 Data');

		//var_dump($this->pool);
	}

	function getStoresFromPool() {
		$reflectionClass = new ReflectionClass(EphemeralQueueStorePool::class);
		$reflectionProperty = $reflectionClass->getProperty('pool');
		return $reflectionProperty->getValue($this->pool);
	}

	function getQueueItemsFromStore($store) {
		$reflectionClass = new ReflectionClass(EphemeralQueueStore::class);
		$reflectionProperty = $reflectionClass->getProperty('items');
		return $reflectionProperty->getValue($store);
	}

	function testDefaultPool() :void {
		$this->assertCount(1, $this->getStoresFromPool());
		$store = $this->pool->lookupQueueStore($this->defaultNamespace, 'mixed');
		$items = $this->getQueueItemsFromStore($store);
		$this->assertCount(3, $items);
	}

	function testAddNewPool() :void {
		$store = $this->pool->lookupQueueStore('Pool 2', 'mixed');
		$this->assertCount(2, $this->getStoresFromPool());
	}

	function testClearExistingPool() :void {
		$this->pool->clear();
		$existingPools = $this->getStoresFromPool();
		$this->assertCount(0, $existingPools);
	}
}