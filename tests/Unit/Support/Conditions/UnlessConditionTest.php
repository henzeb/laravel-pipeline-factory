<?php

namespace Henzeb\Pipeline\Tests\Unit\Support\Conditions;

use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Support\Conditions\UnlessPipeCondition;
use PHPUnit\Framework\TestCase;

class UnlessConditionTest extends TestCase
{
    public function testReturnsTrue()
    {
        $condition = new UnlessPipeCondition(
            new class implements PipeCondition {
                public function test($passable): bool
                {
                    return $passable === 'hello';
                }
            }
        );
        $this->assertTrue($condition->test('world'));
    }

    public function testReturnsFalse()
    {
        $condition = new UnlessPipeCondition(
            new class implements PipeCondition {
                public function test($passable): bool
                {
                    return $passable === 'hello';
                }
            }
        );
        $this->assertFalse($condition->test('hello'));
    }
}
