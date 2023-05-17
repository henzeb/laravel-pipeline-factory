<?php

namespace Henzeb\Pipeline\Tests\Unit\Pipes;

use Exception;
use Henzeb\Pipeline\Contracts\PipelineDefinition;
use Henzeb\Pipeline\Pipes\DefinitionPipe;
use Henzeb\Pipeline\Tests\Helpers\PipeAssertions;
use Orchestra\Testbench\TestCase;

class DefinitionPipeTest extends TestCase
{
    use PipeAssertions;

    public function testShouldImplementHandlesPipe(): void
    {
        $this->assertHandlesPipe(DefinitionPipe::class);
    }

    public function testImplementsHasPipes()
    {
        $this->assertImplementsHasPipes(DefinitionPipe::class);
    }

    public function testNormalizePipelineDefinition()
    {
        $definition = new class implements PipelineDefinition {

            public function definition(): array
            {
                return [
                    fn($passable) => $passable . ' world'
                ];
            }
        };

        $pipe = new DefinitionPipe($definition);
        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world', $result);
    }

    public function testNormalizePipelineDefinitionWhenPreparing()
    {
        $definition = new class implements PipelineDefinition {

            public function definition(): array
            {
                return [
                    fn($passable, $next) => $next($passable . ' world')
                ];
            }
        };

        $pipe = new DefinitionPipe($definition);

        $pipe->preparePipes(
            function (array $pipes) {
                $pipes[] = fn($passable, $next) => $next($passable . '!');
                return $pipes;
            }
        );

        $result = $pipe->__invoke('hello', fn($p) => $p);

        $this->assertEquals('hello world!', $result);
    }

    public function testNormalizePipelineDefinitionDelaysUntilExecution()
    {
        $definition = new class implements PipelineDefinition {

            public function definition(): array
            {
                throw new Exception('Succesfully delayed!');
            }
        };

        $pipe = new DefinitionPipe($definition);

        $this->expectExceptionMessage('Succesfully delayed!');

        $pipe->__invoke('hello', fn($p) => $p);
    }
}
