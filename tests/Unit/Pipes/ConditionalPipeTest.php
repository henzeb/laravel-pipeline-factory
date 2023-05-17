<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Henzeb\Pipeline\Contracts\PipeCondition;
use Henzeb\Pipeline\Pipes\ConditionalPipe;
use Henzeb\Pipeline\Pipes\ContextlessPipe;
use Henzeb\Pipeline\Pipes\RescuePipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Illuminate\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;
use stdClass;

class ConditionalPipeTest extends TestCase
{
    use PipeAssertions;

    private function getPipe(string $append): stdClass
    {
        return new class($append) extends stdClass {
            public function __construct(private string $append)
            {
            }

            public function handle(string $passable, \Closure $next): mixed
            {
                return $next($passable . ' ' . $this->append);
            }
        };
    }

    private function getCondition(): PipeCondition
    {
        return new class implements PipeCondition {
            public function test($passable): bool
            {
                return $passable === 'hello';
            }
        };
    }

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(RescuePipe::class);
    }

    public function testImplementsHasPipes()
    {
        $this->assertImplementsHasPipes(ContextlessPipe::class);
    }

    public function testWhen()
    {
        $conditional = (new ConditionalPipe())->when(
            fn($passable) => $passable === 'hello',
            [$this->getPipe('world')]
        );

        $result = $conditional->__invoke('hello', fn($passable) => $passable);

        $this->assertEquals('hello world', $result);

        $result = $conditional->__invoke('world', fn($passable) => $passable);

        $this->assertEquals('world', $result);
    }

    public function testWhenWithPipeCondition()
    {
        $conditional = (new ConditionalPipe())->when(
            $this->getCondition(),
            [$this->getPipe('world')]
        );

        $result = $conditional->__invoke('hello', fn($passable) => $passable);

        $this->assertEquals('hello world', $result);

        $result = $conditional->__invoke('world', fn($passable) => $passable);

        $this->assertEquals('world', $result);
    }

    public function testUnless()
    {
        $conditional = (new ConditionalPipe())->unless(
            fn($passable) => $passable === 'hello',
            $this->getPipe('world')
        );

        $result = $conditional->__invoke('hello', fn($passable) => $passable);

        $this->assertEquals('hello', $result);

        $result = $conditional->__invoke('world', fn($passable) => $passable);

        $this->assertEquals('world world', $result);
    }

    public function testUnlessWithPipeCondition()
    {
        $conditional = (new ConditionalPipe())->unless(
            $this->getCondition(),
            $this->getPipe('world')
        );

        $result = $conditional->__invoke('hello', fn($passable) => $passable);

        $this->assertEquals('hello', $result);

        $result = $conditional->__invoke('world', fn($passable) => $passable);

        $this->assertEquals('world world', $result);
    }

    public function testElse()
    {
        $conditional = (new ConditionalPipe())->when(
            fn($passable) => $passable === 'hello',
            $this->getPipe('world')
        )->else(
            $this->getPipe('hello')
        );

        $result = $conditional->__invoke('hello', fn($passable) => $passable);
        $this->assertEquals('hello world', $result);

        $result = $conditional->__invoke('world', fn($passable) => $passable);
        $this->assertEquals('world hello', $result);
    }

    public function testStopWhenMatches(): void
    {
        $conditional = (new ConditionalPipe())->when(
            fn($passable) => $passable === 'hello',
            $this->getPipe('world'),
        )->when(
            fn($passable) => $passable === 'world',
            $this->getPipe('forum')
        )->stopProcessingIfWhenMatches();

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('error')
            ])->thenReturn();

        $this->assertEquals('hello world', $result);

        $result = resolve(Pipeline::class)
            ->send('world')
            ->through([
                $conditional,
                $this->getPipe('error')
            ])->thenReturn();

        $this->assertEquals('world forum', $result);

        $result = resolve(Pipeline::class)
            ->send('hello world')
            ->through([
                $conditional,
                $this->getPipe('record')
            ])->thenReturn();

        $this->assertEquals('hello world record', $result);
    }

    public function testStopUnlessMatches(): void
    {
        $conditional = (new ConditionalPipe())->unless(
            fn($passable) => $passable === 'hello',
            $this->getPipe('world'),
        )->unless(
            fn($passable) => $passable === 'hello',
            $this->getPipe('forum')
        )->stopProcessingIfUnlessMatches();

        $result = resolve(Pipeline::class)
            ->send('bye')
            ->through([
                $conditional,
                $this->getPipe('world')
            ])->thenReturn();

        $this->assertEquals('bye world forum', $result);

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('world')
            ])->thenReturn();

        $this->assertEquals('hello world', $result);
    }

    public function testStopProcessingWhenDoesntMatch(): void
    {
        $conditional = (new ConditionalPipe())
            ->when(fn() => false, $this->getPipe('world'))
            ->unless(fn() => true, $this->getPipe('friend'))
            ->stopProcessingIfNothingMatches();

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello', $result);
    }

    public function testDoesntStopProcessingWhenDoesntMatchByDefault()
    {
        $conditional = (new ConditionalPipe())
            ->when(fn() => false, $this->getPipe('world'))
            ->unless(fn() => true, $this->getPipe('friend'));


        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello people', $result);
    }

    public function testDoesStopWhenSpecificCondition()
    {
        $conditional = (new ConditionalPipe())
            ->when(fn() => false, $this->getPipe('worldly'))
            ->when(fn() => true, $this->getPipe('friend'), true);

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello friend', $result);
    }

    public function testDoesNotStopWhenSpecificCondition()
    {
        $conditional = (new ConditionalPipe())
            ->when(fn() => true, $this->getPipe('worldly'))
            ->when(fn() => false, $this->getPipe('friend'), true);

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello worldly people', $result);
    }

    public function testDoesStopUnlessSpecificCondition()
    {
        $conditional = (new ConditionalPipe())
            ->unless(fn() => false, $this->getPipe('worldly'))
            ->unless(fn() => true, $this->getPipe('friend'), true);

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello worldly people', $result);
    }

    public function testDoesNotStopUnlessSpecificCondition()
    {
        $conditional = (new ConditionalPipe())
            ->unless(fn() => true, $this->getPipe('worldly'))
            ->unless(fn() => false, $this->getPipe('friend'), true);

        $result = resolve(Pipeline::class)
            ->send('hello')
            ->through([
                $conditional,
                $this->getPipe('people')
            ])->thenReturn();

        $this->assertEquals('hello friend', $result);
    }

    public function testPreparesPipesWhen(): void
    {
        $pipe = new ConditionalPipe();
        $expectedArray = [
            RescuePipe::class
        ];
        $pipe->when(fn() => true, $expectedArray);

        $pipe->preparePipes(
            function (array $actualArray) use ($expectedArray) {
                $this->assertSame($expectedArray, $actualArray);

                return [
                    fn() => 'hello world'
                ];
            }
        );
        $this->assertEquals('hello world', $pipe->__invoke('hello', fn($p) => $p));
    }

    public function testPreparesPipesUnless(): void
    {
        $pipe = new ConditionalPipe();
        $expectedArray = [
            RescuePipe::class
        ];
        $pipe->unless(fn() => false, $expectedArray);

        $pipe->preparePipes(
            function (array $actualArray) use ($expectedArray) {
                $this->assertSame($expectedArray, $actualArray);

                return [
                    fn() => 'hello world'
                ];
            }
        );
        $this->assertEquals('hello world', $pipe->__invoke('hello', fn($p) => $p));
    }

    public function testPreparesPipesElse(): void
    {
        $pipe = new ConditionalPipe();
        $expectedArray = [
            RescuePipe::class
        ];
        $pipe->else($expectedArray);

        $pipe->preparePipes(
            function (array $actualArray) use ($expectedArray) {
                $this->assertSame($expectedArray, $actualArray);

                return [
                    fn() => 'hello world'
                ];
            }
        );
        $this->assertEquals('hello world', $pipe->__invoke('hello', fn($p) => $p));
    }
}
