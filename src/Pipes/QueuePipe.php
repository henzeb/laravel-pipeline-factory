<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;

class QueuePipe implements HasPipes
{
    use HandlesPipe;

    private ?Closure $whenQueue = null;

    public function __construct(private mixed $pipes)
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        tap(
            dispatch(
                function () use ($viaMethod, $passable) {
                    $this->sendThroughSubPipeline(
                        $this->pipes,
                        $passable,
                        fn(mixed $passable) => $passable,
                        $viaMethod
                    );
                }
            ),
            $this->whenQueue
        );

        return $next($passable);
    }

    public function whenQueue(callable $whenQueue): self
    {   $this->whenQueue = $whenQueue;
        return $this;
    }
}
