<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;

class ResolvingPipe implements HasPipes
{
    use HandlesPipe;

    private array $pipes;

    public function __construct(private string $abstract, private array $parameters = [])
    {
        $this->pipes[] = fn() => resolve($this->abstract, $this->parameters);
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return $this->sendThroughSubPipeline(
            $this->pipes[0](),
            $passable,
            $next,
            $viaMethod
        );
    }
}
