<?php

namespace Henzeb\Pipeline\Tests\Helpers;

use Henzeb\Pipeline\Concerns\HandlesPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;

trait PipelineAssertions
{
    protected function assertHandlesPipe(mixed $class): void
    {
        $this->assertTrue(in_array(HandlesPipe::class, class_uses($class)));
    }
}
