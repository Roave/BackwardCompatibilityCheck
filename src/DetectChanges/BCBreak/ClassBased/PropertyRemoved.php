<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function Safe\preg_match;
use function Safe\sprintf;

final class PropertyRemoved implements ClassBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $fromProperties    = $this->accessibleProperties($fromClass);
        $removedProperties = array_diff(
            array_keys($fromProperties),
            array_keys($this->accessibleProperties($toClass))
        );

        return Changes::fromList(...array_map(function (string $property) use ($fromProperties) : Change {
            return Change::removed(
                sprintf('Property %s was removed', $this->formatProperty->__invoke($fromProperties[$property])),
                true
            );
        }, $removedProperties));
    }

    /** @return ReflectionProperty[] */
    private function accessibleProperties(ReflectionClass $class) : array
    {
        return array_filter($class->getProperties(), function (ReflectionProperty $property) : bool {
            return ($property->isPublic()
                || $property->isProtected())
                && ! $this->isInternalDocComment($property->getDocComment());
        });
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
