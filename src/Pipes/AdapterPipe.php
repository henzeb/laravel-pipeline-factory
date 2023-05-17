<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;

class AdapterPipe implements HasPipes
{
    use HandlesPipe;

    public function __construct(
        private mixed  $pipes,
        private ?string $via
    ) {
    }

    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return $this->sendThroughSubPipeline(
            $this->pipes,
            $passable,
            $next,
            $this->via ?? $viaMethod
        );
    }
}
