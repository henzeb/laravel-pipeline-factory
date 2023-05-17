# The Transactional Pipe

The transactional pipe allows you to execute one or more pipes within a Database
Transaction. If one of the pipes fails it rolls back.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::transaction(
    [
        StoreUserPipe::class,
        StoreUserDetails::class
    ]
);
````

The transaction pipe throws the exception, which can be
handled by [The Rescue Pipe](rescue.md).

## Attempts

You can set the attempts a transaction can be retried.

````php
use Henzeb\Pipeline\Facades\Pipe;

Pipe::transaction(
    [
        StoreUserPipe::class,
        StoreUserDetails::class
    ],
    3 // now a transaction will be tried three times before giving up.
);
````
