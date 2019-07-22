<?php


	namespace MehrItBufferTest\Cases\Unit;


	use ArrayObject;
	use MehrIt\Buffer\FlushingBuffer;
	use MehrItBufferTest\TestCase;

	class FlushingBufferTest extends TestCase
	{
		public function testArray_autoFlush() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(2))
				->method('__invoke')
				->withConsecutive(
					[['A', 'B']],
					[['C', 'D']]
				);

			$buffer = new FlushingBuffer(2, $shouldBeCalled);

			$buffer->add('A');
			$buffer->add('B');
			$buffer->add('C');
			$buffer->add('D');
			$buffer->add('E');
		}

		public function testArray_NoAutoFlush() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->never())
				->method('__invoke');

			$buffer = new FlushingBuffer(0, $shouldBeCalled);

			$buffer->add('A');
			$buffer->add('B');
			$buffer->add('C');
			$buffer->add('D');
			$buffer->add('E');
		}

		public function testArray_manualFlush() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(1))
				->method('__invoke')
				->with(['A']);

			$buffer = new FlushingBuffer(2, $shouldBeCalled);

			$buffer->add('A');
			$buffer->flush();
		}

		public function testArray_manualFlushEmpty() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->never())
				->method('__invoke');

			$buffer = new FlushingBuffer(2, $shouldBeCalled);

			$buffer->flush();
		}

		public function testArray_manualFlushEmptyForced() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(1))
				->method('__invoke')
				->with([]);

			$buffer = new FlushingBuffer(2, $shouldBeCalled);

			$buffer->flush(true);
		}

		public function testArray_count() {

			$buffer = new FlushingBuffer(2, function () {
			});

			$this->assertEquals(0, $buffer->count());
			$buffer->add('A');
			$this->assertEquals(1, $buffer->count());
			$buffer->add('B');
			$this->assertEquals(0, $buffer->count());
			$buffer->add('C');
			$this->assertEquals(1, $buffer->count());
			$buffer->add('D');
			$this->assertEquals(0, $buffer->count());
			$buffer->add('E');
			$this->assertEquals(1, $buffer->count());
			$buffer->flush();
			$this->assertEquals(0, $buffer->count());

		}

		public function testCollection_autoFlush() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(2))
				->method('__invoke')
				->withConsecutive(
					[$this->isInstanceOf(ArrayObject::class)],
					[$this->isInstanceOf(ArrayObject::class)]
				);

			$buffer = new FlushingBuffer(2, $shouldBeCalled, function () {
				return new ArrayObject;
			});

			$buffer->add('A');
			$buffer->add('B');
			$buffer->add('C');
			$buffer->add('D');
			$buffer->add('E');
		}

		public function testCollection_manualFlush() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(1))
				->method('__invoke')
				->with($this->isInstanceOf(ArrayObject::class));

			$buffer = new FlushingBuffer(2, $shouldBeCalled, function () {
				return new ArrayObject();
			});

			$buffer->add('A');
			$buffer->flush();
		}

		public function testCollection_manualFlushEmpty() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->never())
				->method('__invoke');

			$buffer = new FlushingBuffer(2, $shouldBeCalled, function () {
				return new ArrayObject();
			});

			$buffer->flush();
		}

		public function testCollection_manualFlushEmptyForced() {
			$shouldBeCalled = $this->getMockBuilder(\stdClass::class)
				->setMethods(['__invoke'])
				->getMock();

			$shouldBeCalled->expects($this->exactly(1))
				->method('__invoke')
				->with($this->isInstanceOf(ArrayObject::class));

			$buffer = new FlushingBuffer(2, $shouldBeCalled, function () {
				return new ArrayObject();
			});

			$buffer->flush(true);
		}

		public function testCollection_count() {

			$buffer = new FlushingBuffer(2, function () {
			}, function () {
				return new ArrayObject();
			});

			$this->assertEquals(0, $buffer->count());
			$buffer->add('A');
			$this->assertEquals(1, $buffer->count());
			$buffer->add('B');
			$this->assertEquals(0, $buffer->count());
			$buffer->add('C');
			$this->assertEquals(1, $buffer->count());
			$buffer->add('D');
			$this->assertEquals(0, $buffer->count());
			$buffer->add('E');
			$this->assertEquals(1, $buffer->count());
			$buffer->flush();
			$this->assertEquals(0, $buffer->count());

		}

		public function testAddWithKey() {
			$buffer = new FlushingBuffer(3, function () {
			});

			$this->assertEquals(0, $buffer->count());
			$buffer->add('A', 'k-1');
			$this->assertEquals(1, $buffer->count());
			$buffer->add('B', 'k-2');
			$this->assertEquals(2, $buffer->count());
			$buffer->add('C', 'k-2');
			$this->assertEquals(2, $buffer->count());
			$buffer->add('D', 'k-3');
			$this->assertEquals(0, $buffer->count());
			$buffer->add('E', 'k-1');
			$this->assertEquals(1, $buffer->count());
			$buffer->flush();
			$this->assertEquals(0, $buffer->count());
		}

		public function testAddMultiple() {
			$buffer = new FlushingBuffer(3, function () {
			});

			$this->assertEquals(0, $buffer->count());
			$this->assertSame($buffer, $buffer->addMultiple(['A', 'B']));
			$this->assertEquals(2, $buffer->count());
			$this->assertSame($buffer, $buffer->addMultiple(['C', 'D']));
			$this->assertEquals(1, $buffer->count());
			$buffer->flush();
			$this->assertEquals(0, $buffer->count());
		}

		public function testAddMultiple_withKeys() {
			$buffer = new FlushingBuffer(3, function () {
			});

			$this->assertEquals(0, $buffer->count());
			$this->assertSame($buffer, $buffer->addMultiple(['k1' => 'A', 'k2' => 'B'], true));
			$this->assertEquals(2, $buffer->count());
			$this->assertSame($buffer, $buffer->addMultiple(['k1' => 'C', 'k2' => 'D'], true));
			$this->assertEquals(2, $buffer->count());
			$this->assertSame($buffer, $buffer->addMultiple(['k3' => 'C', 'k4' => 'D'], true));
			$this->assertEquals(1, $buffer->count());
			$buffer->flush();
			$this->assertEquals(0, $buffer->count());
		}
	}