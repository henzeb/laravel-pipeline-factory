<?php

namespace Henzeb\Pipeline\Factories;

use Closure;
use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Pipes\ConditionalPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Pipes\ResolvingPipe;

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
        return resolve(ConditionalPipe::class)->when($condition, $pipes);
    }

    public function unless(string|callable|PipeCondition $condition, mixed $pipes): ConditionalPipe
    {
        return resolve(ConditionalPipe::class)->unless($condition, $pipes);
    }
}
