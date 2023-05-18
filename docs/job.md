# The Job Pipe

This pipe accepts a FQCN string of a [Laravel Job](https://laravel.com/docs/master/queues#creating-jobs),
an instance of it, or an array of a combination of them.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::job(YourJob::class);
Pipe::job(YourJob::class, ['parameter'=>'value']);

Pipe::job(new YourJob);
Pipe::job(
    [
        new YourJob,
        YourSecondJob::class
    ]
)
````

## Managing depending dispatch

If you need access to `PendingDispatch`, you can use the
`whenQueue` callback.

````php
use Illuminate\Bus\PendingBatch;

Pipeline::through(
    Pipe::job(YourJob::class)->whenQueue(
        function(PendingDispatch $pending) {
            // do your thing
        }
    )
);
````

## Pass the Passable to the job

When you need the passable inside your job, you can use
the `NeedsPassable` interface.

Your Job would look like this:

````php
use Henzeb\Pipeline\Contracts\NeedsPassable;

class YourJob implements ShouldQueue, NeedsPassable
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private mixed $passable;
    // job logic

    public function setPassable(mixed $passable): void
    {
        $this->passable = $passable;
    }
}
````
