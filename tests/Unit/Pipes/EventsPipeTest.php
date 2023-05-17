<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Events\PipelineProcessed;
use Henzeb\Pipeline\Events\PipelineProcessing;
use Henzeb\Pipeline\Events\PipeProcessed;
use Henzeb\Pipeline\Events\PipeProcessing;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Pipes\EventPipe;
use Henzeb\Pipeline\Pipes\EventsPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class EventsPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(EventsPipe::class);
    }

    public function testShouldDispatchPipelineProcessing()
    {
        Event::fake();

        $pipe = new EventsPipe(fn($passable, $next) => $next($passable . ' world'), 'myPipe');

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);

        Event::assertDispatchedTimes(PipelineProcessing::class);

        Event::assertDispatched(function (PipelineProcessing $event) {
            return $event->pipelineId === 'myPipe'
                && $event->passable = 'hello'
                    && $event->pipeCount === 1;
        });
    }

    public function testShouldDispatchPipelineProcessed()
    {
        Event::fake();

        $pipe = new EventsPipe(fn($passable, $next) => $next($passable . ' world'), 'myPipeTwo');

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);

        Event::assertDispatchedTimes(PipelineProcessed::class);

        Event::assertDispatched(function (PipelineProcessed $event) {
            return $event->pipelineId === 'myPipeTwo'
                && $event->passable = 'hello world'
                    && $event->pipeCount === 1;
        });
    }

    public function testShouldDispatchWithZeroPipeCount()
    {
        Event::fake();

        $pipe = new EventsPipe([], 'myPipeTwo');

        $pipe->__invoke('hello', fn($p) => $p);

        Event::assertDispatched(function (PipelineProcessing $event) {
            return $event->pipeCount === 0;
        });

        Event::assertDispatched(function (PipelineProcessed $event) {
            return $event->pipeCount === 0;
        });

    }

    public function testShouldDispatchWithTwoPipeCount()
    {
        Event::fake();

        $pipe = new EventsPipe([
            fn() => true,
            fn() => true,
        ], 'myPipeTwo');

        $pipe->__invoke('hello', fn($p) => $p);

        Event::assertDispatched(function (PipelineProcessing $event) {
            return $event->pipeCount === 2;
        });

        Event::assertDispatched(function (PipelineProcessed $event) {
            return $event->pipeCount === 2;
        });
    }

    public function testShouldDispatchPipeAlreadyEvent()
    {
        Event::fake();

        $pipe = new EventsPipe(
            new EventPipe(fn($passable) => $passable . ' world', 'differentPipe', 200), 'myPipe');

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);

        Event::assertDispatched(function (PipeProcessed $event) {
            return $event->pipeId === 1 && $event->pipelineId = 'myPipe';
        });
    }

    public function testThrowExceptionWhenEventsPipeInEventsPipe()
    {
        Event::fake();

        $this->expectException(\RuntimeException::class);

        $pipe = new EventsPipe(
            new EventsPipe(fn() => true, 'subPipe'),
            'myPipe'
        );

        $pipe->__invoke('hello', fn($p) => $p);
    }

    public function testThrowExceptionWhenEventsPipeInNestedEventsPipe()
    {
        Event::fake();

        $this->expectException(\RuntimeException::class);

        $pipe = new EventsPipe(
            new AdapterPipe(new EventsPipe(fn() => true, 'subPipe'), 'handle'),
            'myPipe'
        );

        $pipe->__invoke('hello', fn($p) => $p);
    }

    public function testShouldDispatchEventsWithcorrectIds()
    {
        Event::fake();

        $pipe = new EventsPipe(
            [
                fn($passable, $next) => $passable . ' world',
                fn($passable, $next) => $next($passable . '!')
            ],
            'myPipe'
        );

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world!', $result);

        Event::assertDispatched(
            function (PipeProcessing $event) {
                return $event->passable === 'hello'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 1;
            }
        );

        Event::assertDispatched(
            function (PipeProcessing $event) {
                return $event->passable === 'hello world'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 2;
            }
        );

        Event::assertDispatched(
            function (PipeProcessed $event) {
                return $event->passable === 'hello world'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 1;
            }
        );

        Event::assertDispatched(
            function (PipeProcessed $event) {
                return $event->passable === 'hello world!'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 2;
            }
        );

    }

    public function testShouldDispatchEventsOfChildPipes()
    {
        Event::fake();

        $pipe = new EventsPipe(
            [
                new AdapterPipe(fn($passable, $next) => $passable . ' world', 'handle'),
                new AdapterPipe(
                    new AdapterPipe(fn($passable, $next) => $passable . '!', 'handle'),
                    'handle'
                ),
            ],
            'myPipe'
        );

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world!', $result);

        Event::assertDispatched(
            function (PipeProcessing $event) {
                return $event->passable === 'hello'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 1;
            }
        );

        Event::assertDispatched(
            function (PipeProcessed $event) {
                return $event->passable === 'hello world'
                    && $event->pipelineId === 'myPipe'
                    && $event->pipeId === 1;
            }
        );
    }
}
