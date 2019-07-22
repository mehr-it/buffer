<?php


	namespace MehrItBufferTest\Cases\Unit;


	use ArrayObject;
	use MehrIt\Buffer\ChunkProcessor;
	use MehrItBufferTest\TestCase;

	class ChunkProcessorTest extends TestCase
	{
		public function testGetChunkSize() {
			$gen = new ChunkProcessor([], 3, function ($x) {
				yield $x;
			});

			$this->assertEquals(3, $gen->getChunkSize());
		}

		public function testConsume() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(['a', 'b', 'c', 'd', 'e'], 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'C', 'D', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([['a', 'b'], ['c', 'd'], ['e']], $invokedArgs);

		}

		public function testConsume_fromIterator() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(new \ArrayIterator(['a', 'b', 'c', 'd', 'e']), 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'C', 'D', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([['a', 'b'], ['c', 'd'], ['e']], $invokedArgs);

		}

		public function testConsume_fromEmptyIterator() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(new \EmptyIterator(), 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals([], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 0 iterations
			$this->assertEquals([], $invokedArgs);

		}

		public function testConsume_fromClosure() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(function () {
				return ['a', 'b', 'c', 'd', 'e'];
			}, 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'C', 'D', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([['a', 'b'], ['c', 'd'], ['e']], $invokedArgs);

		}

		public function testConsume_callbackReturningEmptyIterator() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				if (false)
					yield 'A';
			};

			$gen = new ChunkProcessor(new \ArrayIterator(['a', 'b', 'c', 'd', 'e']), 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals([], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([['a', 'b'], ['c', 'd'], ['e']], $invokedArgs);

		}

		public function testConsume_callbackReturningSometimesEmptyIterator() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$invokedArgs[] = $arr;

				if (count($invokedArgs) != 2) {
					foreach ($arr as $curr) {
						yield strtoupper($curr);
					}
				}
			};

			$gen = new ChunkProcessor(new \ArrayIterator(['a', 'b', 'c', 'd', 'e']), 2, $fn);

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([['a', 'b'], ['c', 'd'], ['e']], $invokedArgs);

		}

		public function testConsume_withCustomCollection_fromClass() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$this->assertInstanceOf(ArrayObject::class, $arr);

				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(function () {
				return ['a', 'b', 'c', 'd', 'e'];
			}, 2, $fn, function() {
				return new ArrayObject;
			});

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'C', 'D', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([new ArrayObject(['a', 'b']), new ArrayObject(['c', 'd']), new ArrayObject(['e'])], $invokedArgs);

		}

		public function testConsume_withCustomCollection_fromResolverFunction() {

			$invokedArgs = [];
			$fn          = function ($arr) use (&$invokedArgs) {
				$this->assertInstanceOf(ArrayObject::class, $arr);

				$invokedArgs[] = $arr;

				foreach ($arr as $curr) {
					yield strtoupper($curr);
				}
			};

			$gen = new ChunkProcessor(function () {
				return ['a', 'b', 'c', 'd', 'e'];
			}, 2, $fn, function () {
				return new ArrayObject();
			});

			$res = $gen->consume();

			// nothing should be invoked yet
			$this->assertEquals([], $invokedArgs);

			$this->assertEquals(['A', 'B', 'C', 'D', 'E'], iterator_to_array($res));
			$this->assertInstanceOf(\Generator::class, $res);

			// now we expect 3 iterations
			$this->assertEquals([new ArrayObject(['a', 'b']), new ArrayObject(['c', 'd']), new ArrayObject(['e'])], $invokedArgs);

		}
	}