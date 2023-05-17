# The Rescue Pipe

The rescue pipe can be used to deal with exceptions and keep the exception handling
logic out of your pipes.

Under the hood, the rescue pipe uses [rescue](https://laravel.com/docs/master/helpers#method-rescue)
helper from laravel.

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::rescue(fn()=>throw new Exception()),
        Pipe::rescue([YourPipe::class, YourOtherPipe::class]),
        YourThirdPipe::class
    ]
)
````

By default, the rescue pipeline catches any errors and allows the pipeline to be
finished. If an Exception is thrown in `YourPipe`, the pipe hides the error and
continues with `YourThirdPipe`.

## Handling exceptions

Just like the helper, the rescue pipeline accepts a closure for error handling

````php
use Henzeb\Pipeline\Facades\Pipe;

 Pipe::rescue(
    fn() => throw new Exception(),
    function(Throwable $exception, mixed $passable) {
        // do your thing
    }
 );
````

In addition, we also accept an invokable class or a string that points to one:

````php
class YourExceptionHandler
{
    public function __invoke(Throwable $throwable, mixed $passable): void
    {
        // do your thing
    }
}
````

````php
use Henzeb\Pipeline\Facades\Pipe;

 Pipe::rescue(
    fn() => throw new Exception(),
    YourExceptionHandler::class
 );

 Pipe::rescue(
    fn() => throw new Exception(),
    new YourExceptionHandler()
 );
````

## Reporting exceptions

Just like the helper in laravel, the pipe accepts a third parameter which accepts
a boolean or a closure. In addition, it also accepts a invokable class or a string
that points to one:

````php
class YourReportHandler
{
    public function __invoke(Throwable $throwable, mixed $passable): bool
    {
        // do your thing
    }
}
````

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::rescue(
   fn() => throw new Exception(),
   YourExceptionHandler::class,
   true // will report the error, is the default
);

Pipe::rescue(
   fn() => throw new Exception(),
   YourExceptionHandler::class,
   false // will not report
);

Pipe::rescue(
   fn() => throw new Exception(),
   YourExceptionHandler::class,
   YourReportHandler::class
);

Pipe::rescue(
   fn() => throw new Exception(),
   new YourExceptionHandler(),
   new YourReportHandler()
 );
````

## Stop On Failure

If you want to stop the pipeline when an exception is thrown, you can use `stopOnFailure`

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::rescue(YourPipe::class)->stopOnFailure()
    ]
);

````

## Stop When

If you want to stop the pipeline only when a specific failure occurs `stopOnFailure`

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::rescue(YourPipe::class)->stopWhen(YourException::class)
    ]
);

````

## Stop Unless

If you want to stop the pipeline except when a specific failure occurs `stopOnFailure`

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::rescue(YourPipe::class)->stopUnless(YourException::class)
    ]
);

````


