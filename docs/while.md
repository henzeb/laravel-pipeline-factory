# The While Pipe

This introduces the while loop into your pipe.

## While

The first parameter accepts either a `PipeCondition` or
a closure.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::while(
    VideoIsNotReady::class,
    [
        YourPipe::class,
        YourSecondPipe::class,
    ]
);
````

## Do while

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::while(
    VideoIsNotReady::class
)->do(
    [
        YourPipe::class,
        YourSecondPipe::class,
    ]
);
````

## Until

Until is the 'unless' counterpart. Under the hood it still returns
a `WhilePipe`, but uses the opposite result of the condition.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::until(
    VideoIsReady::class, // as long as this returns false, the loop continues.
    [
        YourPipe::class,
        YourSecondPipe::class,
    ]
);

// Or do until

Pipe::until(
    VideoIsReady::class
)->do(
    [
        YourPipe::class,
        YourSecondPipe::class,
    ]
);

````

Note: be aware that a while loop can potentially cause
infinite loops and thus hang your application.
