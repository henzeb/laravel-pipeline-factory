<?php

namespace Henzeb\Pipeline\Tests\Unit\Concerns;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Henzeb\Pipeline\Contracts\PipelineDefinition;
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

            protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
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

            protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
            {
                return $next($viaMethod);
            }
        };

        $pipeline = new Pipeline($this->app);
        $pipeline->via('myViaMethod');
        $closure = Closure::bind($closure, $pipeline, Pipeline::class);

        $this->assertEquals('myViaMethod', $class->__invoke(null, $closure));
    }

    public function testNormalizingPipelines()
    {
        $pipe = new class {
            use HandlesPipe;

            protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
            {
                $definition = new class implements PipelineDefinition {

                    public function definition(): array
                    {
                        return [
                            fn($passable, $next) => $next(++$passable)
                        ];
                    }
                };

                return $this->sendThroughSubPipeline(
                    [
                        $definition,
                        $definition
                    ],
                    $passable,
                    $next,
                    $viaMethod
                );
            }
        };

        $result = $pipe->__invoke(0, fn($p) => $p);
        $this->assertEquals(2, $result);
    }

    public function testNormalizingPipelinesAfterPrepare()
    {
        $pipe = new class implements HasPipes {
            use HandlesPipe;

            private array $pipes;

            public function __construct()
            {
                $definition = new class implements PipelineDefinition {

                    public function definition(): array
                    {
                        return [
                            fn($passable, $next) => $next(++$passable)
                        ];
                    }
                };

                $this->pipes = [
                    $definition,
                    $definition
                ];
            }

            protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
            {
                return $this->sendThroughSubPipeline(
                    $this->pipes,
                    $passable,
                    $next,
                    $viaMethod
                );
            }
        };

        $pipe->preparePipes(
            function (array $pipes) {
                foreach (array_keys($pipes) as $key) {
                    $pipes[$key] = fn($passable, $next) => $next(--$passable);
                }
                return $pipes;
            }
        );

        $result = $pipe->__invoke(0, fn($p) => $p);
        $this->assertEquals(-2, $result);
    }
}
