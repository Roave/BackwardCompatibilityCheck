<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
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

    public function __construct(ClassBased $classBasedComparisons)
    {
        $this->classBasedComparisons = $classBasedComparisons;
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
            $newClass = $newApi->reflect($oldClass->getName());

            $changelog->mergeWith($this->classBasedComparisons->compare($oldClass, $newClass));
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
            $changelog = $this->examineMethod($changelog, $oldClass, $oldMethod, $newClass);
        }

        return $changelog;
    }

    private function examineMethod(
        Changes $changelog,
        ReflectionClass $oldClass,
        ReflectionMethod $oldMethod,
        ReflectionClass $newClass
    ) : Changes {
        if ($oldMethod->isPrivate()) {
            return $changelog;
        }

        try {
            $newMethod = $newClass->getMethod($oldMethod->getName());
        } catch (\OutOfBoundsException $exception) {
            return $changelog->withAddedChange(
                Change::removed(
                    sprintf(
                        'Method %s in class %s has been deleted',
                        $oldMethod->getName(),
                        $oldClass->getName()
                    ),
                    true
                )
            );
        }

        foreach ($oldMethod->getParameters() as $parameterPosition => $oldParameter) {
            $changelog = $this->examineParameter(
                $changelog,
                $parameterPosition,
                $oldClass,
                $oldMethod,
                $oldParameter,
                $newMethod
            );
        }

        return $changelog;
    }

    private function examineParameter(
        Changes $changelog,
        int $parameterPosition,
        ReflectionClass $oldClass,
        ReflectionMethod $oldMethod,
        ReflectionParameter $oldParameter,
        ReflectionMethod $newMethod
    ) : Changes {
        $newParameters = $newMethod->getParameters();
        if (! array_key_exists($parameterPosition, $newParameters)) {
            return $changelog->withAddedChange(
                Change::removed(
                    sprintf(
                        'Parameter %s (position %d) in %s%s%s has been deleted',
                        $oldParameter->getName(),
                        $parameterPosition,
                        $oldClass->getName(),
                        $oldMethod->isStatic() ? '#' : '::',
                        $oldMethod->getName()
                    ),
                    true
                )
            );
        }

        $newParameter = $newParameters[$parameterPosition];

        // @todo check if types changed, or becoming default
        // @todo check if a new param (without a default) was added

        return $changelog;
    }
}
