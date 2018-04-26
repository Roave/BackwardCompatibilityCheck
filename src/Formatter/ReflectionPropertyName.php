<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Formatter;

use Roave\BetterReflection\Reflection\ReflectionProperty;

final class ReflectionPropertyName
{
    public function __invoke(ReflectionProperty $property) : string
    {
        if ($property->isStatic()) {
            return $property->getDeclaringClass()->getName() . '::$' . $property->getName();
        }

        return $property->getDeclaringClass()->getName() . '#$' . $property->getName();
    }
}
