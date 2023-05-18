# The Queue Pipe

The Queue Pipe allows you to queue an entire pipeline or a portion of it.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    Pipe::queue(
        [
            YourPipe::class,
            YourOtherPipe::class,
        ]
    ),
    Pipe::queue(
        YourPipe::class
    ),
    Pipe::queue(
        YourPipeDefiniton::class
    )
);
````

## Managing depending dispatch

If you need access to `PendingDispatch`, you can use the
`whenQueue` callback.

````php
use Illuminate\Bus\PendingBatch;

Pipeline::through(
    Pipe::queue(
        [
            YourPipe::class
        ]
    )->whenQueue(
        function(PendingDispatch $pending) {
            // do your thing
        }
    )
);
````
