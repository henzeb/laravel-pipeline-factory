<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Error;
use Exception;
use Henzeb\Pipeline\Pipes\RescuePipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Pipeline\Pipeline;
use Mockery;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Throwable;

class RescuePipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(RescuePipe::class);
    }

    public function testImplementsHasPipes() {
        $this->assertImplementsHasPipes(RescuePipe::class);
    }

    public function testShouldExecutePipesNormally()
    {
        $pipe = new RescuePipe(
            [
                fn($passable, $next) => $next(++$passable),
                fn($passable, $next) => $next(++$passable),
            ]
        );

        $this->assertEquals(2, $pipe->__invoke(0, fn($p) => $p));
    }

    public function testShouldCatchExceptions()
    {
        $expectedException = new Exception('error');
        $actualException = null;
        $pipe = new RescuePipe(
            [
                fn($passable, $next) => $next(++$passable),
                fn($passable, $next) => throw $expectedException,
            ],
            function (Throwable $e) use (&$actualException) {
                $actualException = $e;
            },
        );


        $this->assertEquals(1, $pipe->__invoke(1, fn($p) => $p));

        $this->assertSame($expectedException, $actualException);
    }

    public function testShouldStopOnFailure()
    {
        $pipe = (new RescuePipe(
            fn() => throw new Exception('test'),
            fn() => true,
        ))->stopOnFailure();

        $result = resolve(Pipeline::class)
            ->send(0)
            ->through(
                [
                    $pipe,
                    fn($passable, $next) => $next(1)
                ]
            )->thenReturn();

        $this->assertEquals(0, $result);
    }

    public function testShouldStopWhen()
    {
        $pipe = (new RescuePipe(
            fn() => throw new Exception('test'),
            fn() => true,
        ))->stopWhen(Exception::class);

        $result = resolve(Pipeline::class)
            ->send(0)
            ->through(
                [
                    $pipe,
                    fn($passable, $next) => $next(1)
                ]
            )->thenReturn();

        $this->assertEquals(0, $result);
    }

    public function testShouldNotStopWhen()
    {
        $pipe = (new RescuePipe(
            fn() => throw new Exception('test'),
            fn() => 1,
        ))->stopWhen(RuntimeException::class);

        $result = resolve(Pipeline::class)
            ->send(0)
            ->through(
                [
                    $pipe,
                    fn($passable, $next) => $next(1)
                ]
            )->thenReturn();

        $this->assertEquals(1, $result);
    }

    public function testShouldstopUnless()
    {
        $pipe = (new RescuePipe(
            fn() => throw new Exception('test'),
            fn() => true,
        ))->stopUnless(RuntimeException::class);

        $result = resolve(Pipeline::class)
            ->send(0)
            ->through(
                [
                    $pipe,
                    fn($passable, $next) => $next(1)
                ]
            )->thenReturn();

        $this->assertEquals(0, $result);
    }

    public function testShouldNotstopUnless()
    {
        $pipe = (new RescuePipe(
            fn() => throw new Exception('test'),
            fn() => true,
        ))->stopUnless(Exception::class);

        $result = resolve(Pipeline::class)
            ->send(0)
            ->through(
                [
                    $pipe,
                    fn($passable, $next) => $next(1)
                ]
            )->thenReturn();

        $this->assertEquals(1, $result);
    }

    public function testShouldReport()
    {

        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $exception = new Exception();

        $exceptionHandler->shouldReceive('report')
            ->once()
            ->with($exception);

        $pipe = new RescuePipe(fn() => throw $exception);
        $pipe->__invoke(null, fn($p) => $p);
    }

    public function testShouldNotReport()
    {
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $exception = new Exception();

        $exceptionHandler->shouldReceive('report')
            ->never();

        $pipe = new RescuePipe(fn() => throw $exception, report: false);
        $pipe->__invoke(null, fn($p) => $p);
    }

    public function testShouldReportBasedOnClosure()
    {
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $exception = new Exception();

        $exceptionHandler->shouldReceive('report')
            ->once()
            ->with($exception);

        $pipe = new RescuePipe(
            fn() => throw $exception,
            report: function (Throwable $e, string $passable) use ($exception) {
                $this->assertEquals('passable', $passable);
                $this->assertSame($exception, $e);
                return true;
            }
        );
        $pipe->__invoke('passable', fn($p) => $p);
    }
}
