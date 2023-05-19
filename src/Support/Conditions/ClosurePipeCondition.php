<?php

namespace Henzeb\Pipeline\Support\Conditions;

use Closure;
use Henzeb\Pipeline\Contracts\PipeCondition;

class ClosurePipeCondition implements PipeCondition
{
    public function __construct(private Closure $closure)
    {
    }

    public function test($passable): bool
    {
        return ($this->closure)($passable);
    }
}
