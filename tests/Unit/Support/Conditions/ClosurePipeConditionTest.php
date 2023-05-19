<?php

namespace Henzeb\Pipeline\Tests\Unit\Support\Conditions;

use Henzeb\Pipeline\Support\Conditions\ClosurePipeCondition;
use PHPUnit\Framework\TestCase;

class ClosurePipeConditionTest extends TestCase
{
    public function testReturnsTrue()
    {
        $condition = new ClosurePipeCondition(
            function (string $passable) {
                return $passable === 'hello';
            }
        );
        $this->assertTrue($condition->test('hello'));
    }

    public function testFalseTrue()
    {
        $condition = new ClosurePipeCondition(
            function (string $passable) {
                return $passable === 'hello';
            }
        );
        $this->assertFalse($condition->test('world'));
    }
}
