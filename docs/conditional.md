# The Conditional Pipe

The conditional pipe allows you to separate the conditional logic from the actual
logic you want to perform.

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

In the above example, if a user is not active, the access is denied, and further
processing of the pipe is cancelled. If The user is active, the `ReportLoginPipe`
is processed and next are processed.

The next pipe Grants access to the user if the user is an admin and denies
access to anyone who isn't. If the user is also a super admin, it grants
super access to the user.

Note: In all methods, you can define pipes just as you would in the `through`
method. This can be a single class or closure, or an array of classes
or closures.

## Stop processing if When matches

In the previous example, we've seen `stopProcessingIfUnlessMatches`. The same
can be done for `when`:

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::when(
    UserIsInactive::class,
    DenyAccessPipe::class
)->stopProcessingIfWhenMatches()
````

This will stop the pipeline from processing subsequent pipes if any of the specified
`when` conditions is matched.

## Stop processing if nothing matches

Sometimes you want to stop the pipeline from processing subsequent pipes when nothing
matches. This is how you would do:

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

In this case, when the user is active, and is an admin, processing subsequent pipes
is stopped.

## Stop processing on conditional basis

You can also stop processing subsequent pipes on conditional basis. `When`
and `Unless`both accept a third parameter that tells the conditional
pipe to stop processing if the condition matches.

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

Note: any matched condition will execute its pipes. So in this case, if a user isn't
an admin, and hasn't validated their email, both `DenyAccessPipe` and
`RequireValidatedEmailPipe` would be processed.
