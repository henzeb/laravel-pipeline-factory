<?php

namespace Henzeb\Pipeline\Concerns;

use Closure;
use Henzeb\Pipeline\Contracts\PipelineDefinition;
use Henzeb\Pipeline\Facades\Pipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionFunction;
use Throwable;

trait HandlesPipe
{
    abstract protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed;

    private function prepareSubPipeline(mixed $pipes, mixed $passable, string $via): Pipeline
    {
        return resolve(Pipeline::class)
            ->send($passable)
            ->through(
                $this->wrapPipelineDefinitions($pipes)
            )->via($via);
    }

    private function sendThroughSubPipeline(mixed $pipes, mixed $passable, Closure $next, string $via = 'handle'): mixed
    {
        return $this->prepareSubPipeline($pipes, $passable, $via)
            ->then($next);
    }

    private function wrapPipelineDefinitions(mixed $pipes): array
    {
        return array_map(
            fn($pipe) => ($pipe instanceof PipelineDefinition ? Pipe::definition($pipe) : $pipe),
            Arr::wrap($pipes)
        );
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(mixed $passable, Closure $next): mixed
    {
        return $this->handlePipe(
            $this->parseMethodNameFromClosure($next),
            $passable,
            $next
        );
    }

    /**
     * @throws ReflectionException
     */
    private function parseMethodNameFromClosure(Closure $next): string
    {
        $reflectionFunction = (new ReflectionFunction($next));
        $reflectionScope = $reflectionFunction->getClosureScopeClass();

        if ($reflectionScope->getName() === Pipeline::class) {
            $property = $reflectionScope->getProperty('method');
            $property->setAccessible(true);
            return $property->getValue($reflectionFunction->getClosureThis());
        }

        return 'handle';
    }

    public function preparePipes(Closure $prepare): void
    {
        $this->pipes = $prepare(Arr::wrap($this->pipes));
    }
}
