<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Generator;
use Henzeb\Pipeline\Pipes\EachPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Orchestra\Testbench\TestCase;

class EachPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(EachPipe::class);
    }

    public function testShouldImplementsHasPipes(): void
    {
        $this->assertImplementsHasPipes(EachPipe::class);
    }

    public function testNonArray()
    {
        $pipe = new EachPipe(
            [
                fn($passable, $next) => $next(++$passable),
                fn($passable, $next) => $next(++$passable)
            ]
        );

        $this->assertEquals(2, $pipe->__invoke(0, fn($p)=>$p));
    }

    public function testArray()
    {
        $pipe = new EachPipe(
            [
                fn($passable, $next) => $next(++$passable),
                fn($passable, $next) => $next(++$passable)
            ]
        );

        $this->assertEquals([2,2], $pipe->__invoke([0,0], fn($p)=>$p));
    }

    public function testGenerator()
    {
        $yield1 = new class {
            public int $number = 0;
        };
        $yield2 = new class {
            public int $number = 0;
        };


        $pipe = new EachPipe(
            [
                fn($passable, $next) => $next(tap($passable, fn($passable)=>++$passable->number)),
                fn($passable, $next) => $next(tap($passable, fn($passable)=>++$passable->number)),
            ]
        );

        $generator = (function($yield1, $yield2): Generator {
            yield $yield1;
            yield $yield2;
        })($yield1, $yield2);

        $this->assertSame($generator, $pipe->__invoke($generator, fn($p)=>$p));

        $this->assertEquals(2, $yield1->number);
        $this->assertEquals(2, $yield2->number);

    }
}
