<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;

class ResolvingPipe
{
    use HandlesPipe;

    public function __construct(private string $abstract, private array $parameters = [])
    {
    }

    private function handlePipe(string $method, mixed $passable, Closure $next): mixed
    {
        return $this->sendThroughSubPipeline(
            resolve($this->abstract, $this->parameters),
            $passable,
            $next,
            $method
        );
    }
}
