<?php


	namespace MehrIt\Buffer;


	use Closure;

	/**
	 * Implements a generator yielding the generated items from a given callback. The callback receives the data in chunks of given size.
	 * @package ItsMieger\LaravelExt\Util
	 */
	class ChunkProcessor
	{
		/**
		 * Creates a processor for the given data and consumes all data using returned generator
		 * @param iterable|Closure $data The data to split into chunks. May also be a closure returning the data
		 * @param int $size The buffer size. You may pass 0 or a negative value if the buffer should not be flushed automatically.
		 * @param callable $chunkHandler Handler function which will be called for each chunk. It will receive buffer contents as first parameter and is expected to return an iterable.
		 * @param callable $collectionResolver Resolver for the underlying collection. This is called each time an empty collection is initialized and must return an
		 * empty collection instance. If omitted an array is used as underlying collection.
		 * @param bool $keepKeys True if to keep keys
		 * @return \Generator
		 */
		public static function process($data, $size, callable $chunkHandler, $collectionResolver = null, bool $keepKeys = false) {
			yield from (new static($data, $size, $chunkHandler, $collectionResolver, $keepKeys))
				->consume();
		}

		/**
		 * @var int
		 */
		protected $chunkSize;

		/**
		 * @var iterable
		 */
		protected $data;

		/**
		 * @var callable
		 */
		protected $chunkHandler;

		/**
		 * @var callable
		 */
		protected $collectionResolver;

		/**
		 * @var bool
		 */
		protected $keepKeys = false;


		/**
		 * Creates a new instance
		 * @param iterable|Closure $data The data to split into chunks. May also be a closure returning the data
		 * @param int $size The buffer size. You may pass 0 or a negative value if the buffer should not be flushed automatically.
		 * @param callable $chunkHandler Handler function which will be called for each chunk. It will receive buffer contents as first parameter and is expected to return an iterable.
		 * @param callable $collectionResolver Resolver for the underlying collection. This is called each time an empty collection is initialized and must return an
		 * empty collection instance. If omitted an array is used as underlying collection.
		 * @param bool $keepKeys True if to keep keys
		 */
		public function __construct($data, $size, callable $chunkHandler, $collectionResolver = null, bool $keepKeys = false) {
			$this->data               = $data;
			$this->chunkSize          = $size;
			$this->chunkHandler       = $chunkHandler;
			$this->collectionResolver = $collectionResolver;
			$this->keepKeys           = $keepKeys;
		}

		/**
		 * Gets the size of the chunks
		 * @return int The buffer size
		 */
		public function getChunkSize() {
			return $this->chunkSize;
		}

		/**
		 * Creates a new buffer
		 * @return array|mixed The new collection
		 */
		protected function newBuffer() {

			if ($resolver = $this->collectionResolver) {
				// use collection resolver
				return call_user_func($resolver);
			}

			// simply use array
			return [];
		}


		/**
		 * Consumes all elements from the generator
		 * @return \Generator
		 */
		public function consume() {
			$flushHandler = $this->chunkHandler;
			$bufferSize   = $this->chunkSize;

			$buffer = $this->newBuffer();

			$data = $this->data;
			if ($data instanceof Closure)
				$data = $data();

			$i = 0;
			foreach ($data as $curr) {
				$buffer[] = $curr;

				++$i;
				if ($i == $bufferSize) {

					if (!$this->keepKeys) {
						foreach (call_user_func($flushHandler, $buffer) as $currValue) {
							yield $currValue;
						}
					}
					else {
						yield from call_user_func($flushHandler, $buffer);
					}

					$buffer = $this->newBuffer();
					$i      = 0;
				}
			}

			if ($i > 0) {
				if (!$this->keepKeys) {
					foreach (call_user_func($flushHandler, $buffer) as $currValue) {
						yield $currValue;
					}
				}
				else {
					yield from call_user_func($flushHandler, $buffer);
				}
			}

		}

	}