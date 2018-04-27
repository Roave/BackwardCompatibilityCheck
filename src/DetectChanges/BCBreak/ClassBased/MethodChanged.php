<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_combine;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_reduce;
use function strtolower;

final class MethodChanged implements ClassBased
{
    /** @var MethodBased */
    private $checkMethod;

    public function __construct(MethodBased $checkMethod)
    {
        $this->checkMethod = $checkMethod;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $methodsFrom   = $this->methods($fromClass);
        $methodsTo     = $this->methods($toClass);
        $commonMethods = array_intersect_key($methodsFrom, $methodsTo);

        return array_reduce(
            array_keys($commonMethods),
            function (Changes $accumulator, string $methodName) use ($methodsFrom, $methodsTo) : Changes {
                return $accumulator->mergeWith($this->checkMethod->__invoke(
                    $methodsFrom[$methodName],
                    $methodsTo[$methodName]
                ));
            },
            Changes::empty()
        );
    }

    /** @return ReflectionMethod[] indexed by lower case method name */
    private function methods(ReflectionClass $class) : array
    {
        $methods = $class->getMethods();

        return array_combine(
            array_map(function (ReflectionMethod $method) : string {
                return strtolower($method->getName());
            }, $methods),
            $methods
        );
    }
}
