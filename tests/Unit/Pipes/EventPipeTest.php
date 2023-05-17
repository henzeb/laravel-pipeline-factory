<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Events\PipeProcessed;
use Henzeb\Pipeline\Events\PipeProcessing;
use Henzeb\Pipeline\Pipes\EventPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class EventPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(EventPipe::class);
    }

    public function testDispatchesPipeProcessing()
    {
        Event::fake();

        $pipe = new EventPipe(
            fn($passable, $next) => $next($passable . ' world'), 'myPipe'
        );

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);

        Event::assertDispatchedTimes(PipeProcessing::class);

        Event::assertDispatched(function (PipeProcessing $event) {
            return $event->pipe === \Closure::class
                && $event->pipelineId = 'myPipe'
                    && $event->pipeId === 1
                    && $event->passable === 'hello';
        });
    }

    public function testDispatchesPipeProcessed()
    {
        Event::fake();

        $pipe = new EventPipe(
            fn($passable, $next) => $next($passable . ' world'), 'myPipe'
        );

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);

        Event::assertDispatchedTimes(PipeProcessed::class);

        Event::assertDispatched(function (PipeProcessed $event) {
            return $event->pipe === \Closure::class
                && $event->pipelineId = 'myPipe'
                    && $event->pipeId === 1
                    && $event->passable === 'hello world';
        });
    }


    public function testDispatchesEventsWithDifferentPipeId()
    {
        Event::fake();

        $pipe = new EventPipe(
            fn($passable, $next) => $next($passable), 'myPipe'
        );

        $pipe->setPipeId(3);

        $pipe->__invoke('hello', fn($p) => $p);

        Event::assertDispatched(function (PipeProcessing $event) {
            return $event->pipeId === 3;
        });

        Event::assertDispatched(function (PipeProcessed $event) {
            return $event->pipeId === 3;
        });
    }

    public function testDispatchesEventsWithDifferentPipelineId()
    {
        Event::fake();

        $pipe = new EventPipe(
            fn($passable, $next) => $next($passable), 'myPipe'
        );

        $pipe->setPipelineId('anotherPipe');

        $pipe->__invoke('hello', fn($p) => $p);

        Event::assertDispatched(function (PipeProcessing $event) {
            return $event->pipelineId === 'anotherPipe';
        });

        Event::assertDispatched(function (PipeProcessed $event) {
            return $event->pipelineId === 'anotherPipe';
        });
    }

    public function testShouldExecutePipeProcessedWhenPipeIsNotUsingNext()
    {
        Event::fake();

        $pipe = new EventPipe(
            fn($passable, $next) => true, 'myPipe'
        );

        $pipe->__invoke('hello', fn($p) => $p);

        Event::assertDispatched(function (PipeProcessed $event) {
            return $event->passable === true;
        });

    }
}
