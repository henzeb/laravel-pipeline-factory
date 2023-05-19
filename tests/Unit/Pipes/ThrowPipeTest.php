<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Exception;
use Henzeb\Pipeline\Contracts\NeedsPassable;
use Henzeb\Pipeline\Pipes\ThrowPipe;
use Mockery;
use PHPUnit\Framework\TestCase;

class ThrowPipeTest extends TestCase
{
    public function testThrowsException()
    {
        $exception = new Exception('test');
        $pipe = new ThrowPipe($exception);

        $this->expectExceptionObject($exception);

        $pipe->__invoke('', fn() => true);
    }

    public function testThrowsExceptionThatNeedsPassable()
    {
        $exception = new class extends Exception implements NeedsPassable {

            public string $passable = '';
            public function setPassable(mixed $passable): void
            {
                $this->passable = $passable;
            }
        };

        $pipe = new ThrowPipe($exception);

        try {
            $pipe->__invoke('hello world', fn() => true);
        } catch (Exception $e) {
            $this->assertEquals('hello world', $e->passable);
        }
    }

    public function testThrowsExceptionWithClosure()
    {

        $exception = new Exception('test');

        $closure = function ($passable) use ($exception) {
            $this->assertEquals('hello world', $passable);
            return $exception;
        };

        $pipe = new ThrowPipe($closure);

        $this->expectExceptionObject($exception);

        $pipe->__invoke('hello world', fn() => true);
    }

}
