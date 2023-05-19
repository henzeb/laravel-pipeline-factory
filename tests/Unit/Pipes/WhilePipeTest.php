<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Pipes\WhilePipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Mockery;
use Orchestra\Testbench\TestCase;

class WhilePipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(WhilePipe::class);
    }

    public function testShouldImplementsHasPipes(): void
    {
        $this->assertImplementsHasPipes(WhilePipe::class);
    }

    public function testWhileLoop() {
        $while = new WhilePipe(
            new class implements PipeCondition
            {
                public function test($passable): bool
                {
                    return $passable !== 3;
                }
            },
            [
                fn($p, $n) => $n(++$p)
            ]
        );

        $this->assertEquals(3, $while->__invoke(0, fn($p)=>$p));
    }

    public function testDoWhileLoop() {
        $while = (new WhilePipe(
            new class implements PipeCondition
            {
                public function test($passable): bool
                {
                    return $passable !== 3;
                }
            },

        ))->do(
            [
                fn($p, $n) => $n(++$p)
            ]
        );

        $this->assertEquals(3, $while->__invoke(0, fn($p)=>$p));
    }

    public function testWhileLoopBeginsWithZero() {

        $condition = Mockery::mock(PipeCondition::class);
        $condition->expects('test')->with(0);

        (new WhilePipe(
            $condition,
            [
                fn($p, $n) => $n(++$p)
            ]
        ))->__invoke(0, fn($p)=>$p);
    }

    public function testDoWhileLoopBeginsWithOne() {

        $condition = Mockery::mock(PipeCondition::class);
        $condition->expects('test')->with(1);

        (new WhilePipe(
            $condition,
        ))->do(
            [
                fn($p, $n) => $n(++$p)
            ]
        )->__invoke(0, fn($p)=>$p);
    }

}
