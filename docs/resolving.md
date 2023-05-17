# The Resolving Pipe

Laravel Pipeline resolves out of the box, so in many cases you are ok. But sometimes
you need to resolve a pipe using objects that depend on a certain state.

Of course, you can instantiate the pipe on the spot. With this, you defer the
instantiation until you actually need it. This is useful when you are preparing
your pipeline with a Hub or when the pipe is running a lot of executions that
can be heavy on start up.

````php
use App\Models\User;

class UsesObjectsPipe {
    public function __construct(private User $user) {}

    public function handle($passable, $next) {
        // ...
    }
}
````

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;
use App\Models\User;

function executePipeline(User $user) {
    return Pipeline::through(
        Pipe::resolve(UsesObjectsPipe::class, ['user'=> $user])
    )->thenReturn();
}
````
