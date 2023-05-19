<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Henzeb\Pipeline\Contracts\PipeCondition;

class WhilePipe implements HasPipes
{
    use HandlesPipe;

    private bool $doWhile = false;

    public function __construct(
        private PipeCondition $condition,
        private mixed         $pipes = []
    )
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        if ($this->doWhile) {
            return $next(
                $this->doWhile($passable, $viaMethod)
            );
        }

        return $next(
            $this->while($passable, $viaMethod)
        );
    }

    public function do(mixed $pipes): self
    {
        $this->doWhile = true;
        $this->pipes = $pipes;
        return $this;
    }

    private function while(mixed $passable, string $viaMethod): mixed
    {
        while ($this->condition->test($passable)) {
            $passable = $this->sendThroughSubPipeline(
                $this->pipes,
                $passable,
                fn(mixed $passable) => $passable,
                $viaMethod
            );
        }
        return $passable;
    }

    private function doWhile(mixed $passable, string $viaMethod): mixed
    {
        do {
            $passable = $this->sendThroughSubPipeline(
                $this->pipes,
                $passable,
                fn(mixed $passable) => $passable,
                $viaMethod
            );
        } while ($this->condition->test($passable));

        return $passable;
    }
}
