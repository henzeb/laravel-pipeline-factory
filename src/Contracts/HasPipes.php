<?php

namespace Henzeb\Pipeline\Contracts;

use Closure;

interface HasPipes
{
    public function preparePipes(Closure $prepare): void;
}
