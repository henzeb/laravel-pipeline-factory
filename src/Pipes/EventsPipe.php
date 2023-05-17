<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Henzeb\Pipeline\Events\PipelineProcessed;
use Henzeb\Pipeline\Events\PipelineProcessing;
use Illuminate\Support\Arr;
use RuntimeException;

class EventsPipe
{
    use HandlesPipe;

    private int $pipeCount = 0;

    public function __construct(private mixed $pipes, private string $pipelineId)
    {
        $this->pipes = Arr::wrap($this->pipes);
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        $pipes = $this->preparePipesForEvents($this->pipes);

        PipelineProcessing::dispatch($this->pipelineId, $this->pipeCount, $passable);

        $passable = $this->sendThroughSubPipeline(
            $pipes,
            $passable,
            fn(mixed $passable) => $passable,
            $viaMethod
        );

        PipelineProcessed::dispatch($this->pipelineId, $this->pipeCount, $passable);

        return $next($passable);
    }

    private function preparePipesForEvents(array $pipes): array
    {
        return array_map(
            function (mixed $pipe) {

                if (is_a($pipe, EventPipe::class)) {
                    return $pipe->setPipeId(
                        $this->incrementAndReturnNewPipeId()
                    )->setPipelineId($this->pipelineId);
                }

                if (is_a($pipe, EventsPipe::class)) {
                    throw new RuntimeException('Cannot have an EventsPipe within another EventsPipe.');
                }

                if (class_implements($pipe, HasPipes::class)) {
                    $pipe->preparePipes(
                        fn(array $pipes) => $this->preparePipesForEvents($pipes)
                    );
                    return $pipe;
                }

                return $this->encapsulateInEventPipe($pipe);
            },
            $pipes
        );
    }

    private function encapsulateInEventPipe(mixed $pipe): EventPipe
    {
        return resolve(
            EventPipe::class,
            [
                'pipe' => $pipe,
                'pipelineId' => $this->pipelineId,
                'pipeId' => $this->incrementAndReturnNewPipeId()
            ]
        );
    }

    private function incrementAndReturnNewPipeId(): int
    {
        return ++$this->pipeCount;
    }
}
