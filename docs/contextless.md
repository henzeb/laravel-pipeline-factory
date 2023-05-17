# The Contextless Pipe

The contextless pipe comes in handy when you are not really passing anything
through the pipeline or when you have a situation where code needs to run, but
does not need access to the

````php
class YourPipe {
    public function handle($next)
    {
        return $next();
    }
}
````

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::contextless(YourPipe::class),
        Pipe::contextless(fn($next)=>$next()),
        Pipe::contextless([YourPipe::class, fn($next)=>$next()])
    ]
);
````
