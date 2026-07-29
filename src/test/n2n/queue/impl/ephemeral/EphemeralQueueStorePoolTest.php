<?php

namespace n2n\queue\impl\ephemeral;

use function PHPUnit\Framework\assertCount;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;
use ReflectionClass;

class EphemeralQueueStorePoolTest extends TestCase  {
	private EphemeralQueueStorePool $pool;

	private string $namespace;

	function setUp():void {
		$namespace = 'Default Pool';

		$this->pool = new EphemeralQueueStorePool();

		$store = new EphemeralQueueStore();
		$store->add('Test 1 Data');
		$store->add('Test 2 Data');
		$store->add('Test 3 Data');

		/*$this->pool = $this->pool->lookupQueueStore($namespace, '');

		var_dump($this->pool);

		$this->pool[$namespace] = $store;
		*/
	}

	function getStoresFromPool() {
		$reflectionClass = new ReflectionClass(EphemeralQueueStorePool::class);
		$reflectionProperty = $reflectionClass->getProperty('pool');
		return $reflectionProperty->getValue($this->pool);
	}

	function testDefaultPool() :void {
		var_dump($this->pool);
		$this->assertCount(1, $this->getStoresFromPool());
	}
}