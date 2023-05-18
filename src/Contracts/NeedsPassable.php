<?php

namespace Henzeb\Pipeline\Contracts;

interface NeedsPassable
{
    public function setPassable(mixed $passable): void;
}
