<?php

namespace n2n\ephemeral;

use PHPUnit\Framework\TestCase;

class EphemeralQueueStoreTest extends TestCase {

	function setUp():void {
	}

	function testAddItem() {
		$queue = new EphemeralQueueStore();
		$queue->add("Job A");
		$queue->add("Job B");
		$queue->add("Job C");

		$this->assertSame(3, $queue->size());
	}
	/*
	$queue->add("Job A");
	$queue->add("Job B");
	$queue->add("Job C");

	$message = $queue->poll();

	echo $message['payload']; // Job A

	$queue->acknowledge($message['id']);

	$message = $queue->poll();

	echo $message['payload']; // Job B

	$queue->reject($message['id']); // Job B goes back into the queue

	$message = $queue->poll();

	echo $message['payload']; // Job C

	$message = $queue->poll();

	echo $message['payload']; // Job B
	 */
}