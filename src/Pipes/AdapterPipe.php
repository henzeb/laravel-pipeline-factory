<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;

class AdapterPipe
{
    use HandlesPipe;

    public function __construct(
        private mixed  $pipe,
        private string $via
    ) {
    }
    private function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return $this->sendThroughSubPipeline(
            $this->pipe,
            $passable,
            $next,
            $this->via
        );
    }
}
