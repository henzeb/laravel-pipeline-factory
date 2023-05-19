<?php

namespace Henzeb\Pipeline\Factories;

use Closure;
use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Contracts\PipelineDefinition;
use Henzeb\Pipeline\Pipes\ConditionalPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Pipes\DefinitionPipe;
use Henzeb\Pipeline\Pipes\EachPipe;
use Henzeb\Pipeline\Pipes\EventPipe;
use Henzeb\Pipeline\Pipes\EventsPipe;
use Henzeb\Pipeline\Pipes\JobPipe;
use Henzeb\Pipeline\Pipes\QueuePipe;
use Henzeb\Pipeline\Pipes\RescuePipe;
use Henzeb\Pipeline\Pipes\ResolvingPipe;
use Henzeb\Pipeline\Pipes\ThrowPipe;
use Henzeb\Pipeline\Pipes\TransactionPipe;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Throwable;

class PipeFactory
{
    public function adapt(mixed $pipe, string $via = null): AdapterPipe
    {
        return resolve(AdapterPipe::class, ['pipe' => $pipe, 'via' => $via ?? 'handle']);
    }

    public function resolve(string $abstract, array $parameters): ResolvingPipe
    {
        return resolve(ResolvingPipe::class, ['abstract' => $abstract, 'parameters' => $parameters]);
    }

    public function contextless(mixed $pipes): ContextlessPipe
    {
        return resolve(ContextlessPipe::class, ['pipes' => $pipes]);
    }

    public function when(string|callable|PipeCondition $condition, mixed $pipes): ConditionalPipe
    {
        return resolve(ConditionalPipe::class)->when(
            $condition instanceof PipeCondition ? $condition : $this->fromCallable($condition),
            $pipes
        );
    }

    public function unless(string|callable|PipeCondition $condition, mixed $pipes): ConditionalPipe
    {
        return resolve(ConditionalPipe::class)->unless(
            $condition instanceof PipeCondition ? $condition : $this->fromCallable($condition),
            $pipes
        );
    }

    public function transaction(mixed $pipes, int $attempts = 1): TransactionPipe
    {
        return resolve(TransactionPipe::class, ['pipes' => $pipes, 'attempts' => $attempts]);
    }

    public function events(mixed $pipes, string $pipelineId = null): EventsPipe
    {
        return resolve(
            EventsPipe::class,
            [
                'pipes' => $pipes,
                'pipelineId' => $pipelineId ?? Str::random(5)
            ]
        );
    }

    public function event(
        string|Closure $pipe,
        string         $pipelineId = null,
        int            $pipeId = null
    ): EventPipe
    {
        return resolve(
            EventPipe::class,
            array_filter(
                [
                    'pipe' => $pipe,
                    'pipelineId' => $pipelineId ?? Str::random(5),
                    'pipeId' => $pipeId
                ]
            )
        );
    }

    public function rescue(
        mixed                $pipes,
        string|callable      $handler = null,
        bool|string|callable $report = true
    ): RescuePipe
    {
        return resolve(
            RescuePipe::class,
            [
                'pipes' => $pipes,
                'handler' => $this->fromCallable($handler),
                'report' => is_bool($report) ? $report : $this->fromCallable($report)
            ]
        );
    }

    public function definition(PipelineDefinition $definition): DefinitionPipe
    {
        return resolve(DefinitionPipe::class, ['pipelineDefinition' => $definition]);
    }

    public function job(ShouldQueue|string|array $job, array $parameters = []): JobPipe
    {
        return resolve(JobPipe::class, ['job' => $job, 'parameters' => $parameters]);
    }

    public function queue(mixed $pipes): QueuePipe
    {
        return resolve(QueuePipe::class, ['pipes' => $pipes]);
    }

    public function throw(string|Throwable|callable $throwable): ThrowPipe
    {
        if (is_string($throwable)
            && class_exists($throwable)
            && method_exists($throwable, '__invoke')
        ) {
            $throwable = $this->fromCallable($throwable);
        }
        return resolve(
            ThrowPipe::class,
            [
                'throwable' => is_string($throwable) ? resolve($throwable) : $throwable
            ]
        );
    }

    public function each(mixed $pipes): EachPipe
    {
        return resolve(EachPipe::class, ['pipes' => $pipes]);
    }

    private function fromCallable(callable|string|null $callable): ?Closure
    {
        if (is_null($callable)) {
            return null;
        }

        return Closure::fromCallable(
            is_string($callable) ? resolve($callable) : $callable
        );
    }
}
