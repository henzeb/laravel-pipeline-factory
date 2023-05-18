<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Pipes\QueuePipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueuePipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(QueuePipe::class);
    }

    public function testShouldImplementsHasPipes(): void
    {
        $this->assertImplementsHasPipes(QueuePipe::class);
    }


    public function testShouldQueue()
    {
        Queue::fake();
        $actual = null;
        $this->assertEquals('hello', (new QueuePipe(
            [
                function () use (&$actual) {
                    $actual = 'world';
                }
            ]
        ))->__invoke('hello', fn($p) => $p));

        $actualJob = Queue::pushedJobs()[CallQueuedClosure::class][0];
        $actualJob['job']->handle($this->app);

        $this->assertEquals('world', $actual);
    }

    public function testShouldAllowAccessPendingBatch()
    {
        Queue::fake();
        $actual = null;

        $queuePipe = (new QueuePipe(
            [
                function () use (&$actual) {
                    $actual = 'world';
                }
            ]
        ))->whenQueue(function(PendingDispatch $dispatch) {
            $dispatch->onQueue('myQueue');
        });

        $this->assertEquals('hello', $queuePipe->__invoke('hello', fn($p) => $p));

        $actualJob = Queue::pushedJobs()[CallQueuedClosure::class][0];
        $actualJob['job']->handle($this->app);
        $this->assertEquals('myQueue', $actualJob['queue']);

        $this->assertEquals('world', $actual);
    }
}
