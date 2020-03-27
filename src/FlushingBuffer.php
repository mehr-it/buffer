<?php


	namespace MehrIt\Buffer;


	use RuntimeException;

	/**
	 * Implements a buffer which is automatically flushed if it is full.
	 */
	class FlushingBuffer
	{
		/**
		 * @var int
		 */
		protected $bufferSize;

		/**
		 * @var array
		 */
		protected $data = [];

		/**
		 * @var callable
		 */
		protected $flushHandler;

		/**
		 * @var int The data count
		 */
		protected $dataCount;

		/**
		 * @var callable
		 */
		protected $collectionResolver;

		/**
		 * Creates a new instance
		 * @param int $size The buffer size. You may pass 0 or a negative value if the buffer should not be flushed automatically.
		 * @param callable $flushHandler Handler function which will be called on flush and receive the buffer contents as first parameter
		 * @param callable $collectionResolver Resolver for the underlying collection. This is called each time an empty collection is initialized and must return an
		 * empty collection instance. If omitted an array is used as underlying collection.
		 */
		public function __construct($size, callable $flushHandler, $collectionResolver = null) {
			$this->bufferSize         = $size;
			$this->flushHandler       = $flushHandler;
			$this->collectionResolver = $collectionResolver;

			// initialize the collection
			$this->initCollection();
		}

		/**
		 * Adds the given item to the buffer
		 * @param mixed $item The item
		 * @param int|string|null $key The key. If passed the given key is used for insert into collection
		 * @return $this This instance
		 */
		public function add($item, $key = null) {

			if ($key !== null) {
				if (array_key_exists($key, $this->data)) {
					$this->data[$key] = $item;
				}
				else {
					$this->data[$key] = $item;
					++$this->dataCount;
				}
			}
			else {
				// add item
				$this->data[] = $item;
				++$this->dataCount;
			}


			// do we have to flush the buffer?
			if ($this->bufferSize > 0 && $this->dataCount >= $this->bufferSize)
				$this->flush();

			return $this;
		}

		/**
		 * Add multiple items to the buffer
		 * @param array|\Traversable $items The items
		 * @param bool $maintainKeys True if to maintain keys
		 * @return $this
		 */
		public function addMultiple($items, bool $maintainKeys = false) {

			foreach ($items as $key => $item) {
				$this->add($item, $maintainKeys ? $key : null);
			}

			return $this;
		}

		/**
		 * Fills the given keys with the array
		 * @param iterable $keys The keys
		 * @param mixed $value The value
		 * @return $this
		 */
		public function fillKeys($keys, $value) {

			foreach($keys as $currKey) {
				$this->add($value, $currKey);
			}

			return $this;
		}

		/**
		 * Sets the given path's value
		 * @param string|string[]|int[] $path The path
		 * @param mixed $value The value
		 * @return $this
		 */
		public function setPath($path, $value) {

			if (!is_array($path))
				$path = explode('.', $path);

			$rootPath = array_shift($path);

			$item    = ($this->data[$rootPath] ?? []);
			$pointer = &$item;

			foreach($path as $currKey) {
				if (($pointer[$currKey] ?? null) === null || !is_array($pointer[$currKey]))
					$pointer[$currKey] = [];

				$pointer = &$pointer[$currKey];
			}

			$pointer = $value;


			$this->add($item, $rootPath);

			return $this;
		}

		/**
		 * Returns if the buffer item count
		 * @return int The number of items in the buffer
		 */
		public function count() {
			return $this->dataCount;
		}

		/**
		 * Gets the size of the buffer
		 * @return int The buffer size
		 */
		public function getBufferSize() {
			return $this->bufferSize;
		}

		/**
		 * Flushes all data in the buffer
		 * @param bool $flushEmpty If true, the flush handle is invoked event if the buffer is empty.
		 * @return mixed The flush handler return if called. Else null.
		 */
		public function flush($flushEmpty = false) {
			$ret = null;

			if ($flushEmpty || $this->dataCount > 0) {
				$ret = call_user_func($this->flushHandler, $this->data);

				// re-init collection
				$this->initCollection();
			}

			return $ret;
		}

		/**
		 * Initializes the collection
		 */
		protected function initCollection() {

			if ($resolver = $this->collectionResolver) {
				// use collection resolver
				$this->data = call_user_func($resolver);
			}
			else {
				// simply use array
				$this->data = [];
			}

			$this->dataCount = 0;
		}
	}