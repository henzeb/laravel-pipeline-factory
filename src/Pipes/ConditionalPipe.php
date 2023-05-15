<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\PipeCondition;
use Illuminate\Support\Arr;

class ConditionalPipe
{
    use HandlesPipe;

    private array $when = [];
    private array $unless = [];
    private array $else = [];
    private bool $stopIfWhenMatches = false;
    private bool $stopIfUnlessMatches = false;
    private bool $stopWhenNothingMatches = false;

    private function handlePipe(string $methodName, mixed $passable, Closure $next): mixed
    {
        $matches = $this->getPipesBasedOnConditions($passable);

        if ($matches['stop']) {
            $next = fn($passable) => $passable;
        }

        return $this->sendThroughSubPipeline(
            $matches['pipes'] ?: $this->else,
            $passable,
            $next,
            $methodName
        );
    }

    public function when(string|PipeCondition|Closure $condition, mixed $pipes, bool $stopProcessing = false): self
    {
        $this->when[] = [
            'condition' => $this->prepareCondition($condition),
            'pipes' => Arr::wrap($pipes),
            'stop' => $stopProcessing
        ];

        return $this;
    }

    public function unless(string|PipeCondition|Closure $condition, mixed $pipes, bool $stopProcessing = false): self
    {
        $this->unless[] = [
            'condition' => $this->prepareCondition($condition),
            'pipes' => Arr::wrap($pipes),
            'stop' => $stopProcessing
        ];

        return $this;
    }

    public function else(mixed $pipes): self
    {
        array_push($this->else, ...Arr::wrap($pipes));

        return $this;
    }

    public function stopProcessingIfWhenMatches(): self
    {
        $this->stopIfWhenMatches = true;
        return $this;
    }

    public function stopProcessingIfUnlessMatches(): self
    {
        $this->stopIfUnlessMatches = true;
        return $this;
    }

    public function stopProcessingIfNothingMatches(): self
    {
        $this->stopWhenNothingMatches = true;
        return $this;
    }

    private function prepareCondition(string|PipeCondition|Closure $condition): Closure
    {
        if (is_callable($condition)) {
            return Closure::fromCallable($condition);
        }

        return function (mixed $passable) use ($condition): bool {
            static $resolvedCondition = null;
            if (!$resolvedCondition && is_string($condition)) {
                $resolvedCondition = resolve($condition);
            }
            $resolvedCondition ??= $condition;

            return $resolvedCondition->test($passable);
        };
    }

    private function getPipesBasedOnConditions(mixed $passable): array
    {
        list($pipes, $stopProcessing) = $this->getWhenPipesBasedOnCondition($passable);

        list($pipes, $stopProcessing) = $this->getUnlessPipesBasedOnCondition($passable, $pipes, $stopProcessing);

        if ($this->stopWhenNothingMatches && empty($pipes)) {
            $stopProcessing = true;
        }

        return [
            'pipes' => $pipes,
            'stop' => $stopProcessing
        ];
    }

    private function getUnlessPipesBasedOnCondition(mixed $passable, array $pipes, bool $stopProcessing): array
    {
        foreach ($this->unless as $unless) {
            if (!$unless['condition']($passable)) {
                array_push($pipes, ...$unless['pipes']);
                $stopProcessing = $stopProcessing ?: ($this->stopIfUnlessMatches ?: $unless['stop']);
            }
        }
        return array($pipes, $stopProcessing);
    }

    private function getWhenPipesBasedOnCondition(mixed $passable): array
    {
        $stopProcessing = false;
        $pipes = [];

        foreach ($this->when as $when) {
            if ($when['condition']($passable)) {
                array_push($pipes, ...$when['pipes']);
                $stopProcessing = $stopProcessing ?: ($this->stopIfWhenMatches ?: $when['stop']);
            }
        }

        return [$pipes, $stopProcessing];
    }
}
