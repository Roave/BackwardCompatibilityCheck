<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use const CASE_UPPER;
use function array_change_key_case;
use function array_combine;
use function array_diff_key;
use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

final class MethodRemoved implements ClassBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $removedMethods = array_diff_key(
            array_change_key_case($this->accessibleMethods($fromClass), CASE_UPPER),
            array_change_key_case($this->accessibleMethods($toClass), CASE_UPPER)
        );

        return Changes::fromList(...array_values(array_map(function (ReflectionMethod $method) : Change {
            return Change::removed(
                sprintf('Method %s was removed', $this->formatFunction->__invoke($method)),
                true
            );
        }, $removedMethods)));
    }

    /** @return ReflectionMethod[] */
    private function accessibleMethods(ReflectionClass $class) : array
    {
        $methods = array_filter($class->getMethods(), function (ReflectionMethod $method) : bool {
            return $method->isPublic() || $method->isProtected();
        });

        return array_combine(
            array_map(function (ReflectionMethod $method) : string {
                return $method->getName();
            }, $methods),
            $methods
        );
    }
}
