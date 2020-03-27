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

### Adding multiple items

To add multiple items to the buffer at once, the `addMultiple()` method can be used. By default
keys are not preserved. You can change this by passing `true` as second parameter.

    $b = new FlushingBuffer(2, function(data) { /* send data */ });
    $b->addMultiple([1, 2, 3]);
    
    // with preserved keys

    $b->addMultiple(['a' => 1, 'b' => 2, 'c' => 3], true);

### Filling array keys
To fill multiple keys with the same value, the `fillKeys()` method can be used:

    $b = new FlushingBuffer(2, function(data) { /* send data */ });
    $b->fillKeys(['a', 'b', 'c'], 1);
    
This will add/replace the keys `"a"`, `"b"` and `"c"` with the value `1`.


### Setting values by path
The `setPath()` method sets a value for the given array path. The path can be specified as string
using "dot notation" or as array:

    $b = new FlushingBuffer(2, function(data) { /* send data */ });
    
    $b->setPath(['this', 'is', 'a' 'path'], 1);
    
    $b->setPath('this.is.another.path'], 2);
    
This creates new array levels as needed but only root level items affect the buffer count. 
 
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