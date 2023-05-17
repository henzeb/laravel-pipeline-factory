<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Illuminate\Support\Arr;

class ContextlessPipe implements HasPipes
{
    use HandlesPipe;

    public function __construct(private mixed $pipes)
    {
        $this->pipes = Arr::wrap($pipes);
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        $pipes = array_map(fn(mixed $pipe) => $this->carry($pipe, $viaMethod), $this->pipes);

        return $this->sendThroughSubPipeline(
            $pipes,
            $passable,
            $next
        );
    }

    private function carry(mixed $pipe, string $via): Closure
    {
        return function (mixed $passable, Closure $next) use ($pipe, $via): mixed {

            $next = fn(mixed $newPassable = null) => $next($newPassable ?? $passable);

            return is_callable($pipe) ? $pipe($next) : $pipe->$via($next);
        };
    }
}
