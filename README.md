# Laravel Pipeline Factory

[![Build Status](https://github.com/henzeb/laravel-pipeline-factory/workflows/tests/badge.svg)](https://github.com/henzeb/laravel-pipeline-factory/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-pipeline-factory.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-pipeline-factory.svg?style=flat-square)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)
[![Test Coverage](https://api.codeclimate.com/v1/badges/72131e070e5ed1aa4b6a/test_coverage)](https://codeclimate.com/github/henzeb/laravel-pipeline-factory/test_coverage)
[![License](https://img.shields.io/packagist/l/henzeb/laravel-pipeline-factory)](https://packagist.org/packages/henzeb/laravel-pipeline-factory)

Laravel has a convenient Pipeline to be used to separate the logic in
a way that allows modifying an object and allows for easy interchangeable
components.

Laravel Pipeline Factory takes it a step further and gives you a couple
of 'pipes' to help you build a more complex pipeline.

## Example

````php
use Illuminate\Support\Facades\Pipeline;
use Henzeb\Pipeline\Facades\Pipe;
use App\Models\User;

$user = User::find(1);

Pipeline::send($user)
        ->through(
            Pipe::events(
                Pipe::unless(
                    UserEnteredPasswordTwice::class,
                    ReturnInvalidRequestResponse::class
                )->else(
                    Pipe::rescue(
                        Pipe::transaction(
                            [
                                UpdateUser::class,
                                UpdateAddress::class
                            ]
                        ),
                        ReturnFailureResponse::class,
                    )
                ),
                'updateUserDetails'
            )
        )
````

## Installation

Just install with the following command.

```bash
composer require henzeb/laravel-pipeline-factory
```

## usage

Every pipe included in this package is invokable. This means you
don't have to stick with `handle` as it's `via` method.

The following pipes are available:

- [The Adapter Pipe](docs/adapter.md)
- [The Conditional Pipe](docs/conditional.md)
- [The Contextless Pipe](docs/contextless.md)
- [The Job Pipe](docs/job.md)
- [The Events Pipe](docs/events.md)
- [The Queue Pipe](docs/queue.md)
- [The Rescue Pipe](docs/rescue.md)
- [The Resolving Pipe](docs/resolving.md)
- [The Transactional Pipe](docs/transaction.md)

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
