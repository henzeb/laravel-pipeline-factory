# The Throw Pipe

With this pipe, it's easy to throw exceptions.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::throw(AuthorisationDenied::class);

Pipe::throw(new AuthorizationDenied);

Pipe::throw(
    function(mixed $passable): Throwable {
        // do your thing and return or throw a Throwable
    }
);
````

## Use exceptions with passables

You can use the `NeedsPassable` interface when your exception
requires information.

````php
use Henzeb\Pipeline\Contracts\NeedsPassable

class AuthorisationDenied extends Exception implements NeedsPassable
{
    public function setPassable(mixed $passable) : void
    {
        // store the passable
    }
}
````
