<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function array_key_exists;
use function sprintf;

class Comparator
{
    /** @var ClassBased */
    private $classBasedComparisons;

    /**
     * @var FunctionBased
     */
    private $functionBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        FunctionBased $functionBasedComparisons
    ) {
        $this->classBasedComparisons    = $classBasedComparisons;
        $this->functionBasedComparisons = $functionBasedComparisons;
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

            $changelog = $changelog->mergeWith($this->classBasedComparisons->compare($oldClass, $newClass));
        } catch (IdentifierNotFound $exception) {
            $changelog = $changelog->withAddedChange(
                Change::removed(sprintf('Class %s has been deleted', $oldClass->getName()), true)
            );
            return $changelog;
        }

        if ($newClass->isFinal() && ! $oldClass->isFinal()) {
            $changelog = $changelog->withAddedChange(
                Change::changed(sprintf('Class %s is now final', $oldClass->getName()), true)
            );
        }

        foreach ($oldClass->getMethods() as $oldMethod) {
            $changelog = $changelog->mergeWith($this->examineMethod($oldMethod, $newClass));
        }

        return $changelog;
    }

    private function examineMethod(
        ReflectionMethod $oldMethod,
        ReflectionClass $newClass
    ) : Changes {
        if ($oldMethod->isPrivate()) {
            return Changes::new();
        }

        try {
            return $this->functionBasedComparisons->compare(
                $oldMethod,
                $newClass->getMethod($oldMethod->getName())
            );
        } catch (\OutOfBoundsException $exception) {
            return Changes::fromArray([
                Change::removed(
                    sprintf(
                        'Method %s in class %s has been deleted',
                        $oldMethod->getName(),
                        $oldMethod->getDeclaringClass()->getName()
                    ),
                    true
                )
            ]);
        }
    }
}
