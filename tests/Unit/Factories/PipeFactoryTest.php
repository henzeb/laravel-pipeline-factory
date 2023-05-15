<?php

namespace Henzeb\Pipeline\Tests\Unit\Factories;

use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Factories\PipeFactory;
use Henzeb\Pipeline\Pipes\ConditionalPipe;
use Henzeb\Pipeline\Pipes\ContextlessClosurePipe;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Pipes\ResolvingPipe;
use Mockery;
use Orchestra\Testbench\TestCase;
use stdClass;

class PipeFactoryTest extends TestCase
{
    public function testResolvesAdapter(): void
    {
        $adapter = Mockery::mock(AdapterPipe::class);
        $this->app->bind(
            AdapterPipe::class,
            function ($app, array $parameters) use ($adapter) {
                $this->assertEquals([
                    'pipe' => AdapterPipe::class,
                    'via' => 'randomHandle'
                ], $parameters);
                return $adapter;
            }
        );

        $this->assertSame(
            $adapter,
            (new PipeFactory())->adapt(
                AdapterPipe::class, 'randomHandle')
        );
    }

    public function testResolvesAdapterWithoutVia(): void
    {
        $adapter = Mockery::mock(AdapterPipe::class);
        $this->app->bind(
            AdapterPipe::class,
            function ($app, array $parameters) use ($adapter) {
                $this->assertEquals([
                    'pipe' => AdapterPipe::class,
                    'via' => 'handle'
                ], $parameters);
                return $adapter;
            }
        );

        $this->assertSame(
            $adapter,
            (new PipeFactory())->adapt(
                AdapterPipe::class)
        );
    }


    public function testResolvesResolvingPipe()
    {
        $resolvingPipe = Mockery::mock(ResolvingPipe::class);
        $parameters = [
            new stdClass()
        ];

        $this->app->bind(
            ResolvingPipe::class,
            function ($app, array $actualParameters) use ($resolvingPipe, $parameters) {

                $this->assertSame([
                    'abstract' => stdClass::class,
                    'parameters' => $parameters,
                ], $actualParameters);

                return $resolvingPipe;
            }
        );

        $this->assertSame($resolvingPipe, (new PipeFactory())->resolve(stdClass::class, $parameters));
    }

    public function testResolvesContextlessPipe()
    {
        $contextless = Mockery::mock(ContextlessPipe::class);
        $closure = fn() => true;
        $this->app->bind(
            ContextlessPipe::class,
            function ($app, array $parameters) use ($contextless, $closure) {
                $this->assertSame([
                    'pipes' => $closure,
                ], $parameters);
                return $contextless;
            }
        );

        $this->assertSame($contextless, (new PipeFactory())->contextless($closure));
    }

    public function testResolvesConditionalPipeWithWhen() {

        $pipeCondition = Mockery::mock(PipeCondition::class);

        $conditional = Mockery::mock(ConditionalPipe::class);

        $conditional->expects('when')->with($pipeCondition, []);

        $this->app->bind(
            ConditionalPipe::class,
            function () use ($conditional) {
                return $conditional;
            }
        );

        (new PipeFactory())->when($pipeCondition, []);

    }

    public function testResolvesConditionalPipeWithUnless() {

        $pipeCondition = Mockery::mock(PipeCondition::class);

        $conditional = Mockery::mock(ConditionalPipe::class);

        $conditional->expects('unless')->with($pipeCondition, []);

        $this->app->bind(
            ConditionalPipe::class,
            function () use ($conditional) {
                return $conditional;
            }
        );

        (new PipeFactory())->unless($pipeCondition, []);

    }
}
