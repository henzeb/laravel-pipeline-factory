# The Definition Pipe

A complex pipeline can contain reusable or separate sections you want to
abstract away.

All pipes from this package accept a `PipelineDefinition`, but if you want
to use them in the root of your pipeline you can use the Definition pipe.

## The PipelineDefinition object

Inside a `PipelineDefinition` you define what you normally would pass in
the method `through` from the `Pipeline` object.

````php
use Henzeb\Pipeline\Contracts\PipelineDefinition;

class UserDetails implements PipelineDefinition {
    public function definition() : array
    {
        return [
            VerifyUserDetailsPipe::class,
            StoreUserDetailsPipe::class
        ]
    }
}
````

## The DefinitionPipe

The `DefinitionPipe` accepts a single `PipelineDefinition`

````php
use Illuminate\Routing\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    Pipe::definition(UserDetails::class),
    Pipe::definition(PaymentDetails::class)
);

````

## Using with other pipes

When used inside other pipes from this package, it's not needed to wrap
it inside a `DefinitionPipe`.

In the example below, a [Transaction pipe](transaction.md) is used to demonstrate.

````php
use Illuminate\Routing\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    Pipe::transaction(
        [
            UserDetails::class,
            PaymentDetails::class
        ]
    )
);

````
