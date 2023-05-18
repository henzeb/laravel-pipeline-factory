<?php

namespace Henzeb\Pipeline\Tests\Unit\Factories;

use Closure;
use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Factories\PipeFactory;
use Henzeb\Pipeline\Pipes\ConditionalPipe;
use Henzeb\Pipeline\Pipes\ContextlessClosurePipe;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Pipes\EventPipe;
use Henzeb\Pipeline\Pipes\EventsPipe;
use Henzeb\Pipeline\Pipes\JobPipe;
use Henzeb\Pipeline\Pipes\QueuePipe;
use Henzeb\Pipeline\Pipes\RescuePipe;
use Henzeb\Pipeline\Pipes\ResolvingPipe;
use Henzeb\Pipeline\Pipes\TransactionPipe;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
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

    public function testResolvesConditionalPipeWithWhen()
    {

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

    public function testResolvesConditionalPipeWithCallableWhen()
    {

        $pipeCondition = new class {
            public function __invoke()
            {
                return 'test';
            }
        };

        $conditional = Mockery::mock(ConditionalPipe::class);

        $conditional->expects('when')->andReturnUsing(function (Closure $closure) use ($conditional) {
            $this->assertSame('test', $closure());
            return $conditional;
        });

        $conditional->expects('when')->andReturnUsing(function (Closure $closure) use ($conditional) {
            $this->assertSame('test', $closure());
            return $conditional;
        });

        $this->app->bind(
            ConditionalPipe::class,
            function () use ($conditional) {
                return $conditional;
            }
        );

        (new PipeFactory())->when($pipeCondition, []);
        (new PipeFactory())->when($pipeCondition::class, []);

    }

    public function testResolvesConditionalPipeWithUnless()
    {

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

    public function testResolvesConditionalPipeWithCallableUnless()
    {

        $pipeCondition = new class {
            public function __invoke()
            {
                return 'test';
            }
        };

        $conditional = Mockery::mock(ConditionalPipe::class);

        $conditional->expects('unless')->andReturnUsing(function (Closure $closure) use ($conditional) {
            $this->assertSame('test', $closure());
            return $conditional;
        });

        $conditional->expects('unless')->andReturnUsing(function (Closure $closure) use ($conditional) {
            $this->assertSame('test', $closure());
            return $conditional;
        });

        $this->app->bind(
            ConditionalPipe::class,
            function () use ($conditional) {
                return $conditional;
            }
        );

        (new PipeFactory())->unless($pipeCondition, []);
        (new PipeFactory())->unless($pipeCondition::class, []);

    }

    public function testResolvesTransactionalPipe()
    {

        $conditional = Mockery::mock(TransactionPipe::class);

        $arrayOfPipes = [
            fn() => true,
            fn() => true,
        ];

        $this->app->bind(
            TransactionPipe::class,
            function ($app, $parameters) use ($conditional, $arrayOfPipes) {
                $this->assertEquals([
                    'pipes' => $arrayOfPipes,
                    'attempts' => 1
                ], $parameters);
                return $conditional;
            }
        );

        $actual = (new PipeFactory())->transaction($arrayOfPipes);
        $this->assertSame($conditional, $actual);

    }

    public function testResolvesTransactionalPipeWithHigherAttempt()
    {

        $conditional = Mockery::mock(TransactionPipe::class);

        $this->app->bind(
            TransactionPipe::class,
            function ($app, $parameters) use ($conditional) {
                $this->assertEquals([
                    'pipes' => [],
                    'attempts' => 2
                ], $parameters);
                return $conditional;
            }
        );

        $actual = (new PipeFactory())->transaction([], 2);
        $this->assertSame($conditional, $actual);
    }

    public function testResolvesEvent()
    {

        $event = Mockery::mock(EventPipe::class);

        $this->app->bind(
            EventPipe::class,
            function ($app, $parameters) use ($event) {
                $this->assertEquals([
                    'pipe' => ConditionalPipe::class,
                    'pipelineId' => 'aPipeId',

                ], $parameters);
                return $event;
            }
        );

        $this->assertSame($event, (new PipeFactory())->event(ConditionalPipe::class, 'aPipeId'));
    }

    public function testResolvesEventWithPipeId()
    {

        $event = Mockery::mock(EventPipe::class);

        $this->app->bind(
            EventPipe::class,
            function ($app, $parameters) use ($event) {
                $this->assertEquals([
                    'pipe' => ConditionalPipe::class,
                    'pipelineId' => 'aPipeId',
                    'pipeId' => 1

                ], $parameters
                );
                return $event;
            }
        );

        $this->assertSame($event, (new PipeFactory())->event(ConditionalPipe::class, 'aPipeId', 1));
    }

    public function testResolvesEventWithRandomPipeId()
    {

        $event = Mockery::mock(EventPipe::class);

        $pipelineIds = [];

        Str::createRandomStringsUsingSequence([
            'string1',
            'string2'
        ]);

        $this->app->bind(
            EventPipe::class,
            function ($app, $parameters) use ($event, &$pipelineIds) {
                $pipelineIds[] = $parameters['pipelineId'];
                return $event;
            }
        );

        $this->assertSame($event, (new PipeFactory())->event(ConditionalPipe::class));
        $this->assertSame($event, (new PipeFactory())->event(ConditionalPipe::class));

        $this->assertSame([
            'string1',
            'string2'
        ], array_unique($pipelineIds));
    }

    public function testResolvesEvents()
    {
        $events = Mockery::mock(EventsPipe::class);

        $this->app->bind(
            EventsPipe::class,
            function ($app, $parameters) use ($events) {
                $this->assertEquals([
                    'pipes' => [
                        ConditionalPipe::class,
                    ],
                    'pipelineId' => 'pipelineId'
                ], $parameters);
                return $events;
            }
        );

        $this->assertSame($events, (new PipeFactory())->events([ConditionalPipe::class], 'pipelineId'));
    }

    public function testResolvesEventsWithRandomIds()
    {
        $events = Mockery::mock(EventsPipe::class);

        $pipelineIds = [];

        Str::createRandomStringsUsingSequence([
            'string1',
            'string2'
        ]);

        $this->app->bind(
            EventsPipe::class,
            function ($app, $parameters) use ($events, &$pipelineIds) {
                $pipelineIds[] = $parameters['pipelineId'];
                $this->assertEquals([], $parameters['pipes']);
                return $events;
            }
        );

        $this->assertSame($events, (new PipeFactory())->events([]));
        $this->assertSame($events, (new PipeFactory())->events([]));

        $this->assertEquals(
            [
                'string1',
                'string2'
            ],
            $pipelineIds
        );

    }

    public function testRandomPipeIdLength()
    {
        Str::createRandomStringsUsing(function (int $length) {
            $this->assertEquals(5, $length);
            return 'string1';
        });
        (new PipeFactory())->events([]);
        (new PipeFactory())->event(fn() => true);
    }

    public function testShouldResolveRescue()
    {
        $rescue = new RescuePipe([]);

        $this->app->bind(
            RescuePipe::class,
            function ($app, $parameters) use ($rescue) {
                $this->assertEquals([
                    'pipes' => [
                        ConditionalPipe::class,

                    ],
                    'handler' => null,
                    'report' => true
                ], $parameters);
                return $rescue;
            }
        );

        $actual = (new PipeFactory())->rescue(
            [
                ConditionalPipe::class
            ]
        );
        $this->assertSame($rescue, $actual);
    }

    public function testShouldResolveJobPipe()
    {
        $job = new class implements ShouldQueue {
        };

        $jobPipe = Mockery::mock(JobPipe::class);

        $this->app->bind(JobPipe::class, function ($app, array $parameters) use ($job, $jobPipe) {
            $this->assertEquals(
                ['job' => $job::class, 'parameters' => ['myparams' => true]],
                $parameters
            );
            return $jobPipe;
        });

        $actual = (new PipeFactory())->job(
            $job::class,
            [
                'myparams' => true
            ]
        );

        $this->assertSame($jobPipe, $actual);
    }

    public function testShouldResolveQueuePipe()
    {

        $queuePipe = Mockery::mock(QueuePipe::class);

        $this->app->bind(QueuePipe::class, function ($app, array $parameters) use ($queuePipe) {
            $this->assertEquals(
                ['pipes' => [RescuePipe::class, EventPipe::class]],
                $parameters
            );
            return $queuePipe;
        });

        $actual = (new PipeFactory())->queue(
            [RescuePipe::class, EventPipe::class],
        );

        $this->assertSame($queuePipe, $actual);
    }
}
