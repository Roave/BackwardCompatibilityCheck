<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Dict;
use Psl\Regex;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyRemoved implements ClassBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        $fromProperties    = $this->accessibleProperties($fromClass);
        $removedProperties = Dict\diff(
            Vec\keys($fromProperties),
            Vec\keys($this->accessibleProperties($toClass))
        );

        return Changes::fromList(...Vec\map($removedProperties, function (string $property) use ($fromProperties): Change {
            return Change::removed(
                Str\format('Property %s was removed', ($this->formatProperty)($fromProperties[$property])),
                true
            );
        }));
    }

    /**
     * @return array<string, ReflectionProperty>
     */
    private function accessibleProperties(ReflectionClass $class): array
    {
        $classIsOpen = ! $class->isFinal();

        return Dict\filter($class->getProperties(), function (ReflectionProperty $property) use ($classIsOpen): bool {
            return ($property->isPublic()
                || ($classIsOpen && $property->isProtected()))
                && ! $this->isInternalDocComment($property->getDocComment());
        });
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
