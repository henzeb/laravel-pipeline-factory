<?php

namespace Henzeb\Pipeline\Tests\Unit\Concerns;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Illuminate\Pipeline\Pipeline;

use Orchestra\Testbench\TestCase;
use stdClass;

class HandlesPipeTest extends TestCase
{
    public static function providesHandlesPipeScenarios(): array
    {
        return [
            [
                'passable' => null,
            ],
            [
                'passable' => 'passable',
            ],
            [
                'passable' => new stdClass(),
            ]
        ];
    }

    /**
     * @param mixed $expectedPassable
     * @return void
     *
     * @dataProvider providesHandlesPipeScenarios
     */
    public function testHandlesPipe(mixed $expectedPassable): void
    {

        $closure = fn(mixed $actualPassable) => $actualPassable;

        $class = new class {
            use HandlesPipe;

            protected function handlePipe(string $methodName, mixed $passable, Closure $next): mixed
            {
                return $next($passable);
            }
        };

        $this->assertSame($expectedPassable, $class->__invoke($expectedPassable, $closure));
    }

    public function testGetViaMethodFromClosure(): void
    {

        $closure = fn(mixed $passable) => $passable;

        $class = new class {
            use HandlesPipe;

            protected function handlePipe(string $methodName, mixed $passable, Closure $next): mixed
            {
                return $next($methodName);
            }
        };

        $pipeline = new Pipeline($this->app);
        $pipeline->via('myViaMethod');
        $closure = Closure::bind($closure, $pipeline, Pipeline::class);

        $this->assertEquals('myViaMethod', $class->__invoke(null, $closure));
    }
}
