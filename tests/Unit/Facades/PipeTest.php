<?php

namespace Henzeb\Pipeline\Tests\Unit\Facades;

use Henzeb\Pipeline\Facades\Pipe;
use Henzeb\Pipeline\Factories\PipeFactory;
use Orchestra\Testbench\TestCase;

/**
 * @mixin PipeFactory
 */
class PipeTest extends TestCase
{
    public function testShouldHaveCorrectFacadeRoot() {
        $this->assertInstanceOf(PipeFactory::class, Pipe::getFacadeRoot());
    }
}
