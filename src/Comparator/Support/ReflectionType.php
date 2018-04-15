<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\Support;

use Assert\Assert;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;

/**
 * Wrapper class used to resolve target ReflectionClass instances without having to have
 * a {@see \Roave\BetterReflection\Reflector\ClassReflector} thrown around at runtime all
 * over the place.
 *
 * @internal
 */
final class ReflectionType
{
    /** @var BetterReflectionType */
    private $betterReflectionType;

    /** @var ReflectionClass|null */
    private $targetClass;

    private function __construct()
    {
    }

    public static function fromBetterReflectionTypeAndReflector(
        ?BetterReflectionType $reflectionType,
        ClassReflector $classReflector
    ) : ?self {
        if (! $reflectionType) {
            return null;
        }

        $instance = new self();

        $instance->betterReflectionType = $reflectionType;

        if ($reflectionType->isBuiltin()) {
            return $instance;
        }

        $targetClass = $classReflector->reflect($reflectionType->__toString());

        assert($targetClass instanceof ReflectionClass);

        $instance->targetClass = $targetClass;

        return $instance;
    }

    /** @see \Roave\BetterReflection\Reflection\ReflectionType::allowsNull() */
    public function allowsNull() : bool
    {
        return $this->betterReflectionType->allowsNull();
    }

    /** @see \Roave\BetterReflection\Reflection\ReflectionType::allowsNull() */
    public function isBuiltin() : bool
    {
        return $this->betterReflectionType->isBuiltin();
    }

    /** @see \Roave\BetterReflection\Reflection\ReflectionType::__toString() */
    public function targetClass() : ReflectionClass
    {
        Assert::that($this->targetClass)->notNull();
        assert($this->targetClass instanceof ReflectionClass);

        return $this->targetClass;
    }

    /** @see \Roave\BetterReflection\Reflection\ReflectionType::__toString() */
    public function __toString() : string
    {
        return $this->betterReflectionType->__toString();
    }
}
