<?php

namespace Henzeb\Pipeline\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PipeProcessed
{
    use Dispatchable;

    public function __construct(
        public string $pipelineId,
        public int    $pipeId,
        public string $pipe,
        public mixed  $passable
    )
    {
    }
}
