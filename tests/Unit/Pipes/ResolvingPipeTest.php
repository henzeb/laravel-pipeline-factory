<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Closure;
use Henzeb\Pipeline\Pipes\ResolvingPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Pipeline\Pipeline;
use Orchestra\Testbench\TestCase;

class ResolvingPipeTest extends TestCase
{
    use PipeAssertions;

    public function testHandlesPipe(): void
    {
        $this->assertHandlesPipe(ResolvingPipe::class);
    }

    public function testImplementsHasPipes() {
        $this->assertImplementsHasPipes(ResolvingPipe::class);
    }

    public function testResolves(): void
    {
        $this->app->bind('test', function () {
            return new class {
                public function handle(mixed $passable, Closure $next): mixed
                {
                    return $next(
                        [
                            $passable,
                            'test'
                        ]
                    );
                }
            };
        }
        );


        $pipe = new ResolvingPipe('test');

        $this->assertEquals(
            [
                'myPassable',
                'test'
            ],
            $pipe->__invoke('myPassable', fn($p) => $p)
        );

    }

    public function testResolvesWithParameters(): void
    {
        $this->app->bind('test', function ($app, $parameters) {

            return new class($parameters) {
                public function __construct(private array $parameters)
                {

                }

                public function handle(mixed $passable, Closure $next): mixed
                {
                    return $next(
                        [
                            $passable,
                            'test',
                            $this->parameters
                        ]
                    );
                }
            };
        }
        );

        $parameters = ['param' => 'value'];


        $pipe = new ResolvingPipe('test', $parameters);

        $this->assertEquals(
            [
                'myPassable',
                'test',
                $parameters
            ],
            $pipe->__invoke('myPassable', fn($p) => $p)
        );
    }

    public function testStopProcessingWhenNotCallingNext(): void
    {
        $this->app->bind('test', function () {
            return new class {
                public function handle(mixed $passable, Closure $next): mixed
                {
                    return $passable;
                }
            };
        }
        );

        $pipeline = resolve(Pipeline::class)->send('success');

        $pipeline->through([
            new ResolvingPipe('test'),
            fn() => 'fail'
        ]);

        $this->assertEquals('success', $pipeline->thenReturn());
    }
}
