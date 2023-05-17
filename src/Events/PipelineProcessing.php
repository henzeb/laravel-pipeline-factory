<?php

namespace Henzeb\Pipeline\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PipelineProcessing
{
    use Dispatchable;

    public function __construct(
        public string $pipelineId,
        public int    $pipeCount,
        public mixed  $passable
    )
    {
    }
}
