<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_intersect_key;
use function array_keys;

final class PropertyChanged implements ClassBased
{
    /** @var PropertyBased */
    private $checkProperty;

    public function __construct(PropertyBased $checkProperty)
    {
        $this->checkProperty = $checkProperty;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return Changes::fromIterator($this->checkSymbols(
            $fromClass->getProperties(),
            $toClass->getProperties()
        ));
    }

    /**
     * @param ReflectionProperty[] $from
     * @param ReflectionProperty[] $to
     *
     * @return iterable|Change[]
     */
    private function checkSymbols(array $from, array $to) : iterable
    {
        foreach (array_keys(array_intersect_key($from, $to)) as $name) {
            yield from $this->checkProperty->__invoke($from[$name], $to[$name]);
        }
    }
}
