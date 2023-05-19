# The Each Pipe

When you pass in an array of items you want to pass through
the same pipeline, you can use this pipe.

````php
use Henzeb\Pipeline\Facades\Pipe;
use Illuminate\Support\Facades\Pipeline;

Pipeline::send(
    [
        User::find(1),
        User::find(2)
    ]
)->through(
    Pipe::each(
        [
            YourPipe::class,
            YourSecondPipe::class,
        ]
    )
);
````

If it's not an array passed through, the pipe will wrap the
passable in an array.

You can also use a Generator or LazyCollection. This way you
can use this for e.g. large files.

