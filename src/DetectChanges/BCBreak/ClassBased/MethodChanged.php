<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_combine;
use function array_intersect_key;
use function array_keys;
use function array_map;
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
        $commonMethods = array_keys(array_intersect_key($methodsFrom, $methodsTo));

        return Changes::fromIterator((function () use ($methodsFrom, $methodsTo, $commonMethods) {
            foreach ($commonMethods as $methodName) {
                yield from $this->checkMethod->__invoke($methodsFrom[$methodName], $methodsTo[$methodName]);
            }
        })());
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
