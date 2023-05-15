<?php

namespace Henzeb\Pipeline\Concerns;

use Closure;
use Illuminate\Pipeline\Pipeline;
use ReflectionException;
use ReflectionFunction;

trait HandlesPipe
{
    abstract private function handlePipe(string $viaMethod, mixed $passable, Closure $next): mixed;

    private function sendThroughSubPipeline(mixed $pipes, mixed $passable, Closure $next, string $via = 'handle'): mixed
    {
        return resolve(Pipeline::class)
            ->send($passable)
            ->through($pipes)
            ->via($via)
            ->then($next);
    }

    /**
     * @throws ReflectionException
     */
    public function __invoke(mixed $passable, Closure $next): mixed
    {
        return $this->handlePipe(
            $this->parseMethodNameFromClosure($next),
            $passable,
            $next
        );
    }

    /**
     * @throws ReflectionException
     */
    private function parseMethodNameFromClosure(Closure $next): string
    {
        $reflectionFunction = (new ReflectionFunction($next));
        $reflectionScope = $reflectionFunction->getClosureScopeClass();

        if ($reflectionScope->getName() === Pipeline::class) {
            $property = $reflectionScope->getProperty('method');
            $property->setAccessible(true);
            return $property->getValue($reflectionFunction->getClosureThis());
        }

        return 'handle';
    }
}
