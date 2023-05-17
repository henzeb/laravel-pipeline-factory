<?php

namespace Henzeb\Pipeline\Tests\Helpers;

use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Contracts\HasPipes;

trait PipeAssertions
{
    protected function assertHandlesPipe(mixed $class): void
    {
        $this->assertTrue(in_array(HandlesPipe::class, class_uses($class)));
    }

    protected function assertImplementsHasPipes(mixed $class): void
    {
        $this->assertTrue(is_subclass_of($class, HasPipes::class));
    }
}
