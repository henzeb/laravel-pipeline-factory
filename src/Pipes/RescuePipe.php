<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Throwable;

class RescuePipe implements HasPipes
{
    use HandlesPipe;

    private bool $stopOnFailure = false;
    private array $stopWhen = [];
    private array $stopUnless = [];

    public function __construct(
        private mixed        $pipes,
        private ?Closure     $handler = null,
        private Closure|bool $report = true
    )
    {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return rescue(
            fn() => $this->sendThroughSubPipeline(
                $this->pipes,
                $passable,
                $next,
                $viaMethod
            ),
            function (Throwable $thrown) use ($passable, $next) {
                if ($this->handler) {
                    ($this->handler)($thrown, $passable);
                }

                if ($this->shouldStop($thrown)) {
                    return $passable;
                }

                return $next($passable);
            },
            is_callable($this->report)
                ? fn(Throwable $throwable) => ($this->report)($throwable, $passable)
                : $this->report
        );
    }

    public function stopOnFailure(): self
    {
        $this->stopOnFailure = true;

        return $this;
    }

    public function stopWhen(string $throwable): self
    {
        $this->stopWhen[] = $throwable;

        return $this;
    }

    public function stopUnless(string $throwable): self
    {
        $this->stopUnless[] = $throwable;

        return $this;
    }

    private function matchesThrowable(Throwable $thrown, array $throwables): bool
    {
        foreach ($throwables as $throwable) {
            if ($thrown instanceof $throwable) {
                return true;
            }
        }
        return false;
    }

    private function shouldStopWhen(Throwable $throwable): bool
    {
        return $this->matchesThrowable($throwable, $this->stopWhen);
    }

    private function shouldStopUnless(Throwable $throwable): bool
    {
        return !$this->matchesThrowable($throwable, $this->stopUnless);
    }

    private function shouldStop(Throwable $throwable): bool
    {
        if ($this->shouldStopWhen($throwable)) {
            return true;
        }

        if (!empty($this->stopUnless) && $this->shouldStopUnless($throwable)) {
            return true;
        }

        return $this->stopOnFailure;
    }
}
