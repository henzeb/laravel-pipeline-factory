<?php

namespace Henzeb\Pipeline\Pipes;

use Closure;
use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionPipe implements HasPipes
{
    use HandlesPipe;

    public function __construct(
        private mixed $pipes,
        private int   $attempts
    )
    {
    }

    /**
     * @throws Throwable
     */
    protected function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed
    {
        return DB::transaction(
            function () use ($viaMethod, $passable, $next) {
                return $this->sendThroughSubPipeline(
                    $this->pipes,
                    $passable,
                    $next,
                    $viaMethod
                );
            },
            $this->attempts
        );
    }
}
