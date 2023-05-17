<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Closure;
use Exception;
use Henzeb\Pipeline\Pipes\TransactionPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class TransactionPipeTest extends TestCase
{
    use PipeAssertions;

    public function testImplementsHandlesPipe(): void
    {
        $this->assertHandlesPipe(TransactionPipe::class);
    }

    public function testImplementsHasPipes() {
        $this->assertImplementsHasPipes(TransactionPipe::class);
    }

    public function testIfStartsTransaction()
    {
        DB::expects('transaction')->andReturnUsing(
            function (Closure $callable, int $attempts) {
                $this->assertEquals(1, $attempts);
            }
        );
        $pipe = new TransactionPipe([], 1);

        $pipe->__invoke('passable', fn($p) => $p);
    }

    public function testIfStartsTransactionWithDifferentAttempt()
    {
        DB::expects('transaction')->andReturnUsing(
            function (Closure $callable, int $attempts) {
                $this->assertEquals(2, $attempts);
            }
        );
        $pipe = new TransactionPipe([], 2);

        $pipe->__invoke('passable', fn($p) => $p);
    }

    public function testIfRunsPipeline()
    {
        DB::expects('transaction')->andReturnUsing(
            function (Closure $callable, int $attempts) {
                return $callable();
            }
        );

        $pipe = new TransactionPipe([
            fn($passable, $next) => $next($passable + 1),
            fn($passable, $next) => $next($passable + 1)
        ], 1);

        $this->assertEquals(2, $pipe->__invoke(0, fn($p) => $p));
    }

    public function testThrowsException()
    {
        DB::expects('transaction')->andReturnUsing(
            function (Closure $callable) {
                return $callable();
            }
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A DB Exception');

        $pipe = new TransactionPipe([
            fn($passable, $next) => throw new Exception('A DB Exception'),
        ], 1);

        $pipe->__invoke('passable', fn($p) => $p);
    }
}
