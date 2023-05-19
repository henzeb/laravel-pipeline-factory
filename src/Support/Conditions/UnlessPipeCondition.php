<?php

namespace Henzeb\Pipeline\Support\Conditions;

use Henzeb\Pipeline\Contracts\PipeCondition;

class UnlessPipeCondition implements PipeCondition
{
    public function __construct(private PipeCondition $condition)
    {
    }

    public function test($passable): bool
    {
        return !$this->condition->test($passable);
    }
}
