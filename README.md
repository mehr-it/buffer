# Buffer utilities
This is a small library implementing simple buffer utilities for dealing with large data sets.

## Flushing buffers
Flushing buffers implement buffers with a given size which are automatically flushed using a given
handler function when they are full.

You may create an instance as follows. It Takes up to three arguments:

	new FlushingBuffer(10, function(data) { /* send data */ });
	new FlushingBuffer(10, function(data) { /* send data */ }, function() { return collect(); });
	
The first argument specifies the buffer size, the second one a handler function which is called
each time the buffer is full. It receives the buffer data as argument. The third one is optional
and acts as resolver for the underlying data structure to use. If omitted a simple array is used.

New items are added to the buffer using the `add()`-method. Usually you want the buffer to be
flushed a last time, after all data was added, even if it's not full. To achieve this, simply
call the `flush()`-method to manually flush the buffer:

	$b = new FlushingBuffer(2, function(data) { /* send data */ });
	$b->add(1);
	$b->add(2);
	$b->add(3);
	$b->flush();
	
	
You may also specify a key, if you want to replace elements in the buffer at given key. 

	$b = new FlushingBuffer(2, function(data) { /* send data */ });
	$b->add(1, 'key1');
	$b->add(2, 'key1');

Of course replacing and existing element does not increase buffer size and therefore does
not cause a buffer flush.


## Chunk processors
Chunk processors allow to process an `iterable` in chunks and return the processed items as 
generator. They act pretty much like PHP's `array_chunk()` function except they working with
any kind of `iterable` and returning a generator. Thus they are predestined for processing large
data sets.

You may use them like this:

	$in = function() { /* input generator code */ };

	$generator = (new ChunkProcessor($in, 500, function($chunk) {
		yield /* ... */
	}))->consume();