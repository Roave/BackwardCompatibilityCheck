<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MethodChanged implements ClassBased
{
    /**
     * @var MethodBased
     */
    private $checkMethod;

    public function __construct(MethodBased $checkMethod)
    {
        $this->checkMethod = $checkMethod;
    }

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $methodsFrom   = $this->methods($fromClass);
        $methodsTo     = $this->methods($toClass);
        $commonMethods = array_intersect_key($methodsFrom, $methodsTo);

        return array_reduce(
            array_keys($commonMethods),
            function (Changes $accumulator, string $methodName) use ($methodsFrom, $methodsTo) : Changes {
                return $accumulator->mergeWith($this->checkMethod->compare(
                    $methodsFrom[$methodName],
                    $methodsTo[$methodName]
                ));
            },
            Changes::new()
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
