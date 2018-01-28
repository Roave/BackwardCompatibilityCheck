<?php
declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

final class Comparator
{
    public function compare(ClassReflector $oldApi, ClassReflector $newApi): array
    {
        $changelog = [];

        foreach ($oldApi->getAllClasses() as $oldClass) {
            $changelog = $this->examineClass($changelog, $oldClass, $newApi);
        }

        return $changelog;
    }

    private function examineClass(array $changelog, ReflectionClass $oldClass, ClassReflector $newApi): array
    {
        try {
            $newClass = $newApi->reflect($oldClass->getName());
        } catch (IdentifierNotFound $exception) {
            $changelog[] = sprintf('[BC] Class %s has been deleted', $oldClass->getName());
            return $changelog;
        }

        foreach ($oldClass->getMethods() as $oldMethod) {
            $changelog = $this->examineMethod($changelog, $oldClass, $oldMethod, $newClass);
        }

        return $changelog;
    }

    private function examineMethod(
        array $changelog,
        ReflectionClass $oldClass,
        ReflectionMethod $oldMethod,
        ReflectionClass $newClass
    ): array {
        // @todo ignore private methods
        try {
            $newMethod = $newClass->getMethod($oldMethod->getName());
        } catch (\OutOfBoundsException $exception) {
            $changelog[] = sprintf(
                '[BC] Method %s in class %s has been deleted',
                $oldMethod->getName(),
                $oldClass->getName()
            );
            return $changelog;
        }

        foreach ($oldMethod->getParameters() as $oldParameter) {
            $changelog = $this->examineParameter($changelog, $oldClass, $oldMethod, $oldParameter, $newMethod);
        }

        return $changelog;
    }

    private function examineParameter(
        array $changelog,
        ReflectionClass $oldClass,
        ReflectionMethod $oldMethod,
        ReflectionParameter $oldParameter,
        ReflectionMethod $newMethod
    ): array {
        $newParameter = $newMethod->getParameter($oldParameter->getName());

        if (null === $newParameter) {
            $changelog[] = sprintf(
                '[BC] Parameter %s in %s%s%s has been deleted',
                $oldParameter->getName(),
                $oldClass->getName(),
                $oldMethod->isStatic() ? '#' : '::',
                $oldMethod->getName()
            );
            return $changelog;
        }

        // @todo check if types changed, or becoming default
        // @todo check if a new param (without a default) was added

        return $changelog;
    }
}
