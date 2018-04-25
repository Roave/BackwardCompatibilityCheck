<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased\InterfaceBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased\TraitBased;
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

    /** @var TraitBased */
    private $traitBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        InterfaceBased $interfaceBasedComparisons,
        TraitBased $traitBasedComparisons
    ) {
        $this->classBasedComparisons     = $classBasedComparisons;
        $this->interfaceBasedComparisons = $interfaceBasedComparisons;
        $this->traitBasedComparisons     = $traitBasedComparisons;
    }

    public function compare(
        ClassReflector $definedApi,
        ClassReflector $oldApi,
        ClassReflector $newApi
    ) : Changes {
        $changelog = Changes::new();

        $definedApiClassNames = array_map(function (ReflectionClass $class) : string {
            return $class->getName();
        }, $definedApi->getAllClasses());

        foreach ($definedApiClassNames as $apiClassName) {
            /** @var ReflectionClass $oldClass */
            $oldClass  = $oldApi->reflect($apiClassName);
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

        if ($oldClass->isInterface()) {
            return $changelog->mergeWith($this->interfaceBasedComparisons->compare($oldClass, $newClass));
        }

        if ($oldClass->isTrait()) {
            return $changelog->mergeWith($this->traitBasedComparisons->compare($oldClass, $newClass));
        }

        return $changelog->mergeWith($this->classBasedComparisons->compare($oldClass, $newClass));
    }
}
