# Laravel Pipeline Factory

[![Build Status](https://github.com/henzeb/laravel-pipeline-factory/workflows/tests/badge.svg)](https://github.com/henzeb/laravel-pipeline-factory/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-pipeline-factory.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-pipeline-factory.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)
[![Test Coverage](https://api.codeclimate.com/v1/badges/72131e070e5ed1aa4b6a/test_coverage)](https://codeclimate.com/github/henzeb/laravel-pipeline-factory/test_coverage)
[![License](https://img.shields.io/packagist/l/henzeb/laravel-pipeline-factory)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)

Laravel has a convenient Pipeline to be used to separate the logic in a way that allows
modifying an object and allows for easy interchangeable components. 

Laravel Pipeline Factory takes it a step further and gives you a couple of 'pipes' 
to help you build a more complex pipeline.

## Installation

Just install with the following command.

```bash
composer require henzeb/laravel-pipeline-factory
```

## usage

Every pipe included in this package is invokable. This means you don't have to stick 
with `handle` as it's `via` method.

The following pipes are available:

- [The Conditional Pipe](#the-conditional-pipe)
- [The Adapter Pipe](#the-adapter-pipe)
- [The Contextless Pipe](#the-contextless-pipe)
- [The Resolving Pipe](#the-resolving-pipe)

### The Conditional Pipe

The conditional pipe allows you to separate the conditional logic from the actual logic you want to perform.

````php
class IsAdmin implements PipeCondition {
    public function test(User $passable): mixed
    {
        // ...
    }
}
````

Let's take the following example to understand the conditional pipe:

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;

Pipeline::through(
    
    Pipe::unless(
        UserIsActive::class,
        DenyAccessPipe::class,
    )->stopProcessingIfUnlessMatches()
     ->else([ReportLoginPipe::class]),
     
    Pipe::when(
        UserIsAdmin::class, 
        GrantAccessPipe::class
    )->when(
        fn(User $user) => $user->role === 'SuperAdmin',
        GrantSuperAccessPipe::class 
    )->unless(
        UserIsAdmin::class,
        DenyAccessPipe::class,       
    ),    
)

````

In the above example, if a user is not active, the access is denied, and further processing of the pipe is cancelled. If
The user is active, the `ReportLoginPipe` is processed and next are processed.

The next pipe Grants access to the user if the user is an admin and denies access to anyone who isn't. If the user is
also a super admin, it grants super access to the user. 

Note: In all methods, you can define pipes just as you would in the `through` method. This can be a single class or
closure, or an array of classes or closures.

#### Stop processing if When matches

In the previous example, we've seen `stopProcessingIfUnlessMatches`. The same can be done for `when`:

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::when(
    UserIsInactive::class,
    DenyAccessPipe::class
)->stopProcessingIfWhenMatches()
````

This will stop the pipeline from processing subsequent pipes if any of the specified `when` conditions is matched.

#### Stop processing if nothing matches

Sometimes you want to stop the pipeline from processing subsequent pipes when nothing matches. This is how you would do:

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::when(
    UserIsInactive::class,
    DenyAccessPipe::class
)->unless(
    UserIsAdmin::class,
    DenyAccessPipe::class, 
)->stopProcessingIfNothingMatches()
````

In this case, when the user is active, and is an admin, processing subsequent pipes is stopped.

#### Stop processing on conditional basis

You can also stop processing subsequent pipes on conditional basis. `When` and `Unless` both accept a third parameter
that tells the conditional pipe to stop processing if the condition matches.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::when(
    UserIsInactive::class,
    DenyAccessPipe::class,
    true,
)->when(
    UserIsAdmin::class,
    GrantAccess::class, 
)->unless(
    UserIsAdmin::class,
    DenyAccessPipe::class,
    true
)->unless(
    UserHasntValidatedEmail::class,
    RequireValidatedEmailPipe::class
)
````

This will stop processing only when the user is Inactive or the user isn't an admin.

Note: any matched condition will execute its pipes. So in this case, if a user isn't an admin, and hasn't validated
their email, both `DenyAccessPipe` and `RequireValidatedEmailPipe` would be processed.

### The Adapter Pipe
The adapter pipe is a pipe that allows you to add a pipe with a different method. this 
can be for example a pipe that you don't control.

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

In the above example, the `CustomPackagePipe` is wrapped inside an `AdapterPipe`.
This pipe redirects any method call to `customHandle` on the `CustomPackagePipe`.

By default, when the `$via` parameter is not passed, `adapt` will use `handle` as default.

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

### The Contextless Pipe
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
### The Resolving Pipe

Laravel Pipeline resolves out of the box, so in many cases you are ok. But sometimes
you need to resolve a pipe using objects that depend on a certain state.

Of course, you can instantiate the pipe on the spot. With this, you defer the instantiation
until you actually need it. This is useful when you are preparing your pipeline with a Hub or when
the pipe is running a lot of executions that can be heavy on start up.

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

## Testing this package

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed
recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email
henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.
