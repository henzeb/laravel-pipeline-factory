<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Contracts\NeedsPassable;
use Henzeb\Pipeline\Pipes\JobPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class JobPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(JobPipe::class);
    }


    public function testShouldQueue()
    {
        $job = new class implements ShouldQueue {
        };

        Queue::fake();

        $this->assertEquals('hello', (new JobPipe($job))->__invoke('hello', fn($p) => $p));

        Queue::assertPushed(
            $job::class,
            function ($actual) use ($job) {
                return $job === $actual;
            }
        );
    }

    public function testShouldQueueString()
    {
        $job = new class implements ShouldQueue {
        };

        Queue::fake();

        $this->assertEquals('hello', (new JobPipe($job::class))->__invoke('hello', fn($p) => $p));

        Queue::assertPushed($job::class);
    }

    public function testShouldQueueStringWithParameters()
    {
        $job = new class() implements ShouldQueue {
            public function __construct(public ?string $test = null)
            {
            }
        };

        Queue::fake();

        $this->assertEquals(
            'hello',
            (new JobPipe($job::class, ['test' => 'world']))->__invoke('hello', fn($p) => $p)
        );

        Queue::assertPushed($job::class, function ($job) {
            return $job->test = 'world';
        });
    }

    public function testShouldQueueStringTwice()
    {
        $job = new class implements ShouldQueue {
        };

        Queue::fake();

        $this->assertEquals('hello', (new JobPipe([$job::class, $job]))->__invoke('hello', fn($p) => $p));

        $this->assertCount(2, Queue::pushedJobs()[$job::class]);
    }

    public function testShouldQueueWithPassable()
    {
        $job = new class implements ShouldQueue, NeedsPassable {
            public string $passable = '';

            public function setPassable(mixed $passable): void
            {
                $this->passable = $passable;
            }
        };

        Queue::fake();

        $this->assertEquals('hello', (new JobPipe($job::class))->__invoke('hello', fn($p) => $p));

        Queue::assertPushed($job::class, function ($job) {
            return $job->passable === 'hello';
        });
    }

    public function testShouldAllowModifyPendingDispatch()
    {
        $job = new class implements ShouldQueue {
            use InteractsWithQueue, Queueable;
        };

        Queue::fake();

        $pipe = (new JobPipe($job::class))->whenQueue(
            function (PendingDispatch $dispatch) {
                $dispatch->onQueue('world');
            }
        );

        $this->assertEquals('hello', $pipe->__invoke('hello', fn($p) => $p));

        Queue::assertPushed($job::class, function ($job, $queue) {
            return $queue === 'world';
        });
    }
}
