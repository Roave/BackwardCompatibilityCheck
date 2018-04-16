<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_combine;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_values;
use function sprintf;

/**
 * A method that changes from instance to static or the opposite has to be called differently,
 * so any of such changes are to be considered BC breaks
 */
final class MethodScopeChanged implements ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $scopesFrom = $this->methodScopes($fromClass);
        $scopesTo   = $this->methodScopes($toClass);
        $sharedKeys = array_keys(array_intersect_key($scopesFrom, $scopesTo));

        $affectedVisibilities = array_filter(
            array_combine(
                $sharedKeys,
                array_map(
                    function (string $propertyName) use ($scopesFrom, $scopesTo) : array {
                        return [$scopesFrom[$propertyName], $scopesTo[$propertyName]];
                    },
                    $sharedKeys
                )
            ),
            function (array $scopes) : bool {
                return $scopes[0] !== $scopes[1];
            }
        );

        return Changes::fromArray(array_values(array_map(function (
            string $methodName,
            array $visibilities
        ) use (
            $fromClass
        ) : Change {
            return Change::changed(
                sprintf(
                    'Method %s() of class %s changed scope from %s to %s',
                    $fromClass->getMethod($methodName)->getName(),
                    $fromClass->getName(),
                    $visibilities[0],
                    $visibilities[1]
                ),
                true
            );
        }, array_keys($affectedVisibilities), $affectedVisibilities)));
    }

    /** @return string[] instance|static markers, indexed by method name */
    private function methodScopes(ReflectionClass $class) : array
    {
        $methods = array_filter($class->getMethods(), function (ReflectionMethod $method) : bool {
            return $method->isPublic() || $method->isProtected();
        });

        return array_combine(
            array_map(function (ReflectionMethod $method) : string {
                return $method->getName();
            }, $methods),
            array_map(function (ReflectionMethod $method) : string {
                return $method->isStatic() ? 'static' : 'instance';
            }, $methods)
        );
    }
}
