<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\NeedsPassable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class JobPipe
{
    use HandlesPipe;

    private ?Closure $whenQueue = null;

    public function __construct(
        private ShouldQueue|string|array $job,
        private array                    $parameters = []
    )
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        foreach (Arr::wrap($this->job) as $job) {
            tap(
                dispatch(
                    $this->getJob($job, $passable)
                ),
                $this->whenQueue
            );
        }

        return $next($passable);
    }

    public function whenQueue(callable $whenQueue): self
    {
        $this->whenQueue = Closure::fromCallable($whenQueue);
        return $this;
    }

    private function getJob(mixed $job, mixed $passable): ShouldQueue
    {
        if (is_string($job)) {
            $job = resolve($job, $this->parameters);
        }

        if ($job instanceof NeedsPassable) {
            $job->setPassable($passable);
        }

        return $job;
    }
}
