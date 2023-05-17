<?php

namespace Henzeb\Pipeline\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PipeProcessing
{
    use Dispatchable;

    public function __construct(
        public string $pipelineId,
        public int $pipeId,
        public string $pipe,
        public mixed $passable
    )
    {
    }
}
