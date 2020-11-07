<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Roave\BetterReflection\Reflection\ReflectionProperty;

final class ReflectionPropertyName
{
    public function __invoke(ReflectionProperty $property): string
    {
        $declaringClass = $property->getDeclaringClass();
        $className      = $declaringClass->getName();
        if ($declaringClass->isTrait()) {
            $className = $property->getImplementingClass()->getName();
        }

        if ($property->isStatic()) {
            return $className . '::$' . $property->getName();
        }

        return $className . '#$' . $property->getName();
    }
}
