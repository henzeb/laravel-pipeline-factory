# The Adapter Pipe

The adapter pipe is a pipe that allows you to add a pipe with a different
method. this can be for example a pipe that you don't control.

````php
class CustomPackagePipe {
    public function customHandle($passable, $next) {
        // ...
    }
}
````

````php
class YourPipe {
    public function handle($passable, $next) {
        // ...
    }
}
````

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        Pipe::adapt(CustomPackagePipe::class, 'customHandle'),
        YourPipe::class
    ]
);
````

In the above example, the `CustomPackagePipe` is wrapped inside
an `AdapterPipe`. This pipe redirects any method call to `customHandle`
on the `CustomPackagePipe`.

By default, when the `$via` parameter is not passed, `adapt` will use
`handle` as default.

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    [
        CustomPackagePipe::class,
        Pipe::adapt(YourPipe::class)
    ]
)->via('customHandle');
````
