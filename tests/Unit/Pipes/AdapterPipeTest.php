<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Closure;
use Error;
use Henzeb\Pipeline\Pipes\AdapterPipe;
use Henzeb\Pipeline\Tests\Helpers\PipelineAssertions;
use Orchestra\Testbench\TestCase;
use stdClass;

class AdapterPipeTest extends TestCase
{
    use PipelineAssertions;

    public function testHandlesPipe(): void
    {
        $this->assertHandlesPipe(AdapterPipe::class);
    }

    public function testAdapterPipe(){
        $pipe = new AdapterPipe(
            $this->getPipeWithCustomHandle(),
            'myCustomHandle'
        );

        $this->assertEquals('hello', $pipe->__invoke('hello', fn($passable)=>$passable));
    }

    public function testAdapterPipeFail(){
        $pipe = new AdapterPipe(
            $this->getPipeWithCustomHandle(),
            'handle'
        );
        $error = null;
        try {
            $this->assertEquals('hello', $pipe->__invoke('hello', fn($passable) => $passable));
        }catch(Error $e) {
            $error = $e;
        } finally {
            $this->assertInstanceOf(Error::class, $error);
        }

    }


    private function getPipeWithCustomHandle(): stdClass
    {
        return new class extends stdClass {
            public function myCustomHandle(mixed $passable, Closure $next): mixed
            {
                return $next($passable);
            }
        };
    }
}
