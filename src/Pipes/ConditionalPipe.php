<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Support\Conditions\ClosurePipeCondition;
use Illuminate\Support\Arr;

class ConditionalPipe implements HasPipes
{
    use HandlesPipe;

    private array $when = [];
    private array $unless = [];
    private array $else = [];
    private bool $stopIfWhenMatches = false;
    private bool $stopIfUnlessMatches = false;
    private bool $stopWhenNoMatches = false;

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        $matches = $this->getPipesBasedOnConditions($passable);

        if ($matches['stop']) {
            $next = fn($passable) => $passable;
        }

        return $this->sendThroughSubPipeline(
            $matches['pipes'] ?: $this->else,
            $passable,
            $next,
            $viaMethod
        );
    }

    public function when(PipeCondition|Closure $condition, mixed $pipes, bool $stopProcessing = false): self
    {
        $this->when[] = [
            'condition' => $this->prepareCondition($condition),
            'pipes' => Arr::wrap($pipes),
            'stop' => $stopProcessing
        ];

        return $this;
    }

    public function unless(PipeCondition|Closure $condition, mixed $pipes, bool $stopProcessing = false): self
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
        $this->stopWhenNoMatches = true;
        return $this;
    }

    private function prepareCondition(PipeCondition|Closure $condition): PipeCondition
    {
        if ($condition instanceof Closure) {
            return resolve(
                ClosurePipeCondition::class,
                ['closure' => $condition]
            );
        }

        return $condition;
    }

    private function getPipesBasedOnConditions(mixed $passable): array
    {
        list($pipes, $stopProcessing) = $this->getWhenPipesBasedOnCondition($passable);

        list($pipes, $stopProcessing) = $this->getUnlessPipesBasedOnCondition($passable, $pipes, $stopProcessing);

        if ($this->stopWhenNoMatches && empty($pipes)) {
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
            if (!$unless['condition']->test($passable)) {
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
            if ($when['condition']->test($passable)) {
                array_push($pipes, ...$when['pipes']);
                $stopProcessing = $stopProcessing ?: ($this->stopIfWhenMatches ?: $when['stop']);
            }
        }

        return [$pipes, $stopProcessing];
    }

    public function preparePipes(Closure $prepare): void
    {
        $this->when = $this->preparePipesFor($this->when, $prepare);

        $this->unless = $this->preparePipesFor($this->unless, $prepare);

        $this->else = array_merge(... array_map(fn(mixed $pipe) => $prepare([$pipe]), $this->else));
    }

    private function preparePipesFor(array $conditions, Closure $prepare): array
    {
        return array_map(
            function (array $condition) use ($prepare) {
                $condition['pipes'] = $prepare($condition['pipes']);
                return $condition;
            },
            $conditions
        );
    }
}
