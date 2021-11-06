<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\Variance;

use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * This minimal value object carries an {@see ReflectionType} and the {@see Reflector}
 * where it was produced: this is necessary in order to find symbols referenced in a
 * {@see ReflectionType}, so that we can inspect class hierarchies and whether they
 * still respect variance rules.
 *
 * @psalm-immutable
 *
 * @psalm-internal \Roave\BackwardCompatibility
 */
final class TypeWithReflectorScope
{
    public function __construct(
        public ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type,
        public Reflector $originatingReflector
    ) {
    }
}
