<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Events\PipeProcessed;
use Henzeb\Pipeline\Events\PipeProcessing;

class EventPipe
{
    use HandlesPipe;

    public function __construct(
        private string|Closure $pipe,
        private string         $pipelineId,
        private int            $pipeId = 1
    )
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        PipeProcessing::dispatch(
            $this->pipelineId,
            $this->pipeId,
            $this->pipe::class,
            $passable
        );

        $passable = $this->sendThroughSubPipeline(
            $this->pipe,
            $passable,
            fn(mixed $passable) => $passable,
            $viaMethod
        );

        PipeProcessed::dispatch(
            $this->pipelineId,
            $this->pipeId,
            $this->pipe::class,
            $passable
        );

        return $next($passable);
    }

    public function setPipeId(int $pipeId): self
    {
        $this->pipeId = $pipeId;
        return $this;
    }

    public function setPipelineId(string $pipelineId): self
    {
        $this->pipelineId = $pipelineId;
        return $this;
    }
}
