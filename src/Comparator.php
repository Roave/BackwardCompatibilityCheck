<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased\InterfaceBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function sprintf;

class Comparator
{
    /** @var ClassBased */
    private $classBasedComparisons;

    /** @var InterfaceBased */
    private $interfaceBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        InterfaceBased $interfaceBasedComparisons
    ) {
        $this->classBasedComparisons     = $classBasedComparisons;
        $this->interfaceBasedComparisons = $interfaceBasedComparisons;
    }

    public function compare(ClassReflector $oldApi, ClassReflector $newApi) : Changes
    {
        $changelog = Changes::new();

        foreach ($oldApi->getAllClasses() as $oldClass) {
            $changelog = $this->examineClass($changelog, $oldClass, $newApi);
        }

        return $changelog;
    }

    private function examineClass(Changes $changelog, ReflectionClass $oldClass, ClassReflector $newApi) : Changes
    {
        try {
            /** @var ReflectionClass $newClass */
            $newClass = $newApi->reflect($oldClass->getName());
        } catch (IdentifierNotFound $exception) {
            return $changelog->withAddedChange(
                Change::removed(sprintf('Class %s has been deleted', $oldClass->getName()), true)
            );
        }

        if ($oldClass->isInterface() && $newClass->isInterface()) {
            $changelog = $changelog->mergeWith($this->interfaceBasedComparisons->compare($oldClass, $newClass));
        }

        return $changelog->mergeWith($this->classBasedComparisons->compare($oldClass, $newClass));
    }
}
