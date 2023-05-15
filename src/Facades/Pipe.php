<?php

namespace Henzeb\Pipeline\Facades;

use Henzeb\Pipeline\Factories\PipeFactory;
use Illuminate\Support\Facades\Facade;

class Pipe extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PipeFactory::class;
    }
}
