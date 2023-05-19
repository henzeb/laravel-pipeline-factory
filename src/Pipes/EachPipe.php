<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Generator;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Illuminate\Support\Arr;

class EachPipe implements HasPipes
{
    use HandlesPipe;

    public function __construct(private mixed $pipes)
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        $unwrap = false;

        if (!is_iterable($passable)) {
            $unwrap = true;
            $passable = Arr::wrap($passable);
        }

        foreach ($passable as $key => $passableItem) {
            $this->sendThroughSubPipeline(
                $this->pipes,
                $passableItem,
                function (mixed $returnedPassable) use (&$passable, $key) {
                    if ($passable instanceof Generator) {
                        return;
                    }

                    $passable[$key] = $returnedPassable;
                },
                $viaMethod
            );
        }

        if ($unwrap) {
            return $next(reset($passable));
        }

        return $next($passable);
    }
}
