<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\ConstantBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased\InterfaceBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function sprintf;

class Comparator
{
    /** @var ClassBased */
    private $classBasedComparisons;

    /**
     * @var InterfaceBased
     */
    private $interfaceBasedComparisons;

    /**
     * @var MethodBased
     */
    private $methodBasedComparisons;

    /**
     * @var PropertyBased
     */
    private $propertyBasedComparisons;

    /**
     * @var ConstantBased
     */
    private $constantBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        InterfaceBased $interfaceBasedComparisons,
        MethodBased $methodBasedComparisons,
        PropertyBased $propertyBasedComparisons,
        ConstantBased $constantBasedComparisons
    ) {
        $this->classBasedComparisons     = $classBasedComparisons;
        $this->interfaceBasedComparisons = $interfaceBasedComparisons;
        $this->methodBasedComparisons    = $methodBasedComparisons;
        $this->propertyBasedComparisons  = $propertyBasedComparisons;
        $this->constantBasedComparisons  = $constantBasedComparisons;
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

            if ($oldClass->isInterface() && $newClass->isInterface()) {
                $changelog = $changelog->mergeWith($this->interfaceBasedComparisons->compare($oldClass, $newClass));
            }

            $changelog = $changelog->mergeWith($this->classBasedComparisons->compare($oldClass, $newClass));
        } catch (IdentifierNotFound $exception) {
            $changelog = $changelog->withAddedChange(
                Change::removed(sprintf('Class %s has been deleted', $oldClass->getName()), true)
            );
            return $changelog;
        }

        foreach ($oldClass->getMethods() as $oldMethod) {
            $changelog = $changelog->mergeWith($this->examineMethod($oldMethod, $newClass));
        }

        foreach ($oldClass->getProperties() as $oldProperty) {
            $changelog = $changelog->mergeWith($this->examineProperty($oldProperty, $newClass));
        }

        foreach ($oldClass->getReflectionConstants() as $oldConstant) {
            $changelog = $changelog->mergeWith($this->examineConstant($oldConstant, $newClass));
        }

        return $changelog;
    }

    private function examineMethod(ReflectionMethod $oldMethod, ReflectionClass $newClass) : Changes
    {
        $methodName = $oldMethod->getName();

        if (! $newClass->hasMethod($methodName)) {
            return Changes::new();
        }

        return $this->methodBasedComparisons->compare($oldMethod, $newClass->getMethod($methodName));
    }

    private function examineProperty(ReflectionProperty $oldProperty, ReflectionClass $newClass) : Changes
    {
        $newProperty = $newClass->getProperty($oldProperty->getName());

        if (! $newProperty) {
            return Changes::new();
        }

        return $this->propertyBasedComparisons->compare($oldProperty, $newProperty);
    }

    private function examineConstant(ReflectionClassConstant $oldConstant, ReflectionClass $newClass) : Changes
    {
        $newConstant = $newClass->getReflectionConstant($oldConstant->getName());

        if (! $newConstant) {
            return Changes::new();
        }

        return $this->constantBasedComparisons->compare($oldConstant, $newConstant);
    }
}
