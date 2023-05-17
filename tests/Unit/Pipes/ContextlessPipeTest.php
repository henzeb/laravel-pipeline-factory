<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Closure;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContextlessPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(ContextlessPipe::class);
    }

    public function testImplementsHasPipes() {
        $this->assertImplementsHasPipes(ContextlessPipe::class);
    }

    private function getContextlessPipe(string $newPassable = null): stdClass
    {
        return new class($newPassable) extends stdClass {
            public function __construct(private $newPassable = null)
            {
            }

            public function handle(Closure $next): mixed
            {
                if ($this->newPassable) {
                    return $next($this->newPassable);
                }
                return $next();
            }
        };
    }

    public function testPassesNullablePassableToNextClosure()
    {
        $passable = null;
        $next = function ($received) use ($passable) {
            $this->assertEquals($passable, $received);
            return $received;
        };

        $result = (new ContextlessPipe($this->getContextlessPipe()))->__invoke($passable, $next);

        $this->assertEquals(null, $result);
    }

    public function testPassesPassableToNextClosure()
    {
        $passable = 'hello';
        $next = function ($received) use ($passable) {
            $this->assertEquals($passable, $received);
            return $received;
        };
        $result = (new ContextlessPipe($this->getContextlessPipe()))->__invoke($passable, $next);
        $this->assertEquals('hello', $result);
    }

    public function testPassesNewPassableToNextClosure()
    {
        $passable = 'world';
        $next = function ($received) use ($passable) {
            $this->assertEquals($passable, $received);
            return $received;
        };
        $result = (new ContextlessPipe($this->getContextlessPipe('world')))->__invoke('hello', $next);

        $this->assertEquals('world', $result);
    }

    public function testReplacesNullWithNewPassableToNextClosure()
    {
        $passable = 'world';
        $next = function ($received) use ($passable) {
            $this->assertEquals($passable, $received);
            return $received;
        };
        $result = (new ContextlessPipe($this->getContextlessPipe('world')))->__invoke(null, $next);
        $this->assertEquals('world', $result);
    }

    public function testWithClosure() {
        $result = (new ContextlessPipe(function(Closure $next){
            return 'world';
        }))->__invoke('hello', fn()=>true);

        $this->assertEquals('world', $result);

        $result = (new ContextlessPipe(function(Closure $next){
            return $next();
        }))->__invoke('hello', fn($passable)=>$passable);

        $this->assertEquals('hello', $result);
    }

    public function testWithMultiplePipes() {
        $result = (new ContextlessPipe([
            fn($next)=>$next('world'),
            fn($next)=>$next(),
        ]))->__invoke('hello', fn($passable)=>$passable);

        $this->assertEquals('world', $result);
    }
}
