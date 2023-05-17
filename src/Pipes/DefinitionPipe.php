<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Henzeb\Pipeline\Contracts\PipelineDefinition;

class DefinitionPipe implements HasPipes
{
    use HandlesPipe;

    private Closure $prepare;

    public function __construct(private PipelineDefinition $pipelineDefinition)
    {
        $this->prepare = fn(array $pipes)=>$pipes;
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return $this->sendThroughSubPipeline(
            $this->getPipes(),
            $passable,
            $next,
            $viaMethod
        );
    }

    public function preparePipes(Closure $prepare): void
    {
        $this->prepare = $prepare;
    }

    private function getPipes(): array
    {
        return ($this->prepare)($this->pipelineDefinition->definition());
    }
}
