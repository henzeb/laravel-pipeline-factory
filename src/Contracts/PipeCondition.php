<?php

namespace Henzeb\Pipeline\Contracts;

interface PipeCondition
{
    /**
     * @param $passable
     * @return bool
     */
    public function test($passable): bool;
}
