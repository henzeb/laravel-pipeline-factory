<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Contracts\NeedsPassable;
use Throwable;

class ThrowPipe
{
    public function __construct(private Throwable|Closure $throwable)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(mixed $passable, Closure $next): void
    {
        $throwable = value($this->throwable, $passable);

        if ($throwable instanceof NeedsPassable) {
            $throwable->setPassable($passable);
        }

        throw $throwable;
    }
}
